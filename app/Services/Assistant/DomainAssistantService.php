<?php

namespace App\Services\Assistant;

class DomainAssistantService
{
    public function __construct(
        private DomainAssistantOpenRouterClient $client,
        private DomainAssistantDataService $dataService,
    ) {}

    /**
     * @return array{answer: string, tools_invoked: list<string>, tool_traces: list<array{name: string, arguments: array<string, mixed>, result_summary: string}>}
     */
    public function runChat(
        string $userMessage,
        array $historyMessages,
        bool $toolsEnabled,
        bool $showAllRecords,
    ): array {
        $model = (string) config('services.domain_assistant.model');
        $maxIter = (int) config('services.domain_assistant.max_tool_iterations', 5);

        $system = $this->systemPrompt();

        $messages = [
            ['role' => 'system', 'content' => $system],
        ];
        foreach ($historyMessages as $m) {
            $messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $tools = $toolsEnabled ? $this->toolDefinitions() : [];
        $toolsInvoked = [];
        $toolTraces = [];

        $iter = 0;
        while ($iter < $maxIter) {
            $iter++;
            $response = $tools !== []
                ? $this->client->chatCompletionWithTools($model, $messages, $tools, 0.2)
                : $this->client->chatCompletion($model, $messages, 0.2);

            $choice = $response['choices'][0] ?? null;
            if ($choice === null) {
                return [
                    'answer' => 'Assistant returned an empty response.',
                    'tools_invoked' => $toolsInvoked,
                    'tool_traces' => $toolTraces,
                ];
            }

            $message = $choice['message'] ?? [];
            $finishReason = $choice['finish_reason'] ?? null;
            $assistantContent = trim((string) ($message['content'] ?? ''));
            $toolCalls = $message['tool_calls'] ?? null;

            if (is_array($toolCalls) && $toolCalls !== []) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $assistantContent,
                    'tool_calls' => $toolCalls,
                ];

                foreach ($toolCalls as $call) {
                    $id = (string) ($call['id'] ?? '');
                    $fn = $call['function'] ?? [];
                    $name = (string) ($fn['name'] ?? '');
                    $argsRaw = (string) ($fn['arguments'] ?? '{}');
                    $args = [];
                    try {
                        $decoded = json_decode($argsRaw, true, 512, JSON_THROW_ON_ERROR);
                        $args = is_array($decoded) ? $decoded : [];
                    } catch (\Throwable) {
                        $args = [];
                    }

                    $toolsInvoked[] = $name;
                    $result = $this->dataService->execute($name, $args, $showAllRecords);
                    $summary = $this->summarizeToolResult($result);

                    $toolTraces[] = [
                        'name' => $name,
                        'arguments' => $args,
                        'result_summary' => $summary,
                    ];

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $id,
                        'content' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                    ];
                }

                continue;
            }

            if ($assistantContent !== '') {
                return [
                    'answer' => $assistantContent,
                    'tools_invoked' => array_values(array_unique($toolsInvoked)),
                    'tool_traces' => $toolTraces,
                ];
            }

            if ($finishReason === 'stop' || $finishReason === null) {
                return [
                    'answer' => 'No answer was produced. Try rephrasing your question.',
                    'tools_invoked' => array_values(array_unique($toolsInvoked)),
                    'tool_traces' => $toolTraces,
                ];
            }

            break;
        }

        return [
            'answer' => 'Stopped after maximum tool iterations. Please narrow your question.',
            'tools_invoked' => array_values(array_unique($toolsInvoked)),
            'tool_traces' => $toolTraces,
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
You are Sarang ERP Domain Assistant. You answer questions about THIS company's operational data (sales orders, AR sales invoices, purchase orders, delivery orders, goods receipts, inventory, business partners).

Document types (do not confuse them):
- **Sales Invoice / faktur penjualan / AR invoice** (nomor invoice, faktur): use **search_sales_invoices** with the number in **invoice_query**, or **get_sales_invoice_detail** with **invoice_no** when the user wants line items / detail / baris. Invoice lookup searches all active company entities (PT/CV), not only the default entity. Never put an invoice number into search_sales_orders or customer_query on orders.
- **Sales Order** (SO, pesanan penjualan): use **search_sales_orders** with customer name/code or order context — not invoice numbers.

Rules:
- Never invent document numbers, amounts, or IDs. Use the provided tools to fetch live data from the database before stating facts.
- If the user's question needs data, call the appropriate tool with sensible filters (date range, customer/supplier name fragment, status).
- Date filters default to the last 90 days when the user does not specify dates (invoice lookup by **invoice_query** skips the date window so older invoices can be found).
- If no tool fits the question, say that you cannot answer from available tools and suggest contacting an administrator.
- Reply in clear prose. Use bullet lists when listing multiple records.
- Amounts and statuses must match tool output exactly.
TXT;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function toolDefinitions(): array
    {
        $props = fn (array $schema) => $schema;

        return [
            $this->fn('get_erp_summary', 'High-level counts by status for sales orders, purchase orders, delivery orders, and goods receipts within the last 90 days (scoped to the user).', $props([
                'type' => 'object',
                'properties' => new \stdClass,
            ])),
            $this->fn('search_sales_orders', 'Search **sales orders** (SO) by customer name/code fragment, optional status, and date range on order date. Do NOT use for invoice/faktur numbers — use search_sales_invoices.', $props([
                'type' => 'object',
                'properties' => [
                    'customer_query' => ['type' => 'string', 'description' => 'Customer name or code fragment'],
                    'status' => ['type' => 'string', 'description' => 'Exact status or "open" for non-closed'],
                    'date_from' => ['type' => 'string', 'description' => 'Y-m-d'],
                    'date_to' => ['type' => 'string', 'description' => 'Y-m-d'],
                    'limit' => ['type' => 'integer', 'description' => 'Max rows, max 20'],
                ],
            ])),
            $this->fn('search_sales_invoices', 'Search **AR sales invoices** (faktur penjualan) by invoice number, reference number, and/or customer. Pass invoice / faktur number in invoice_query. Searches all active company entities when invoice_query is set (not only default PT/CV).', $props([
                'type' => 'object',
                'properties' => [
                    'invoice_query' => ['type' => 'string', 'description' => 'Invoice number or fragment (invoice_no / reference_no)'],
                    'customer_query' => ['type' => 'string', 'description' => 'Customer name or code (optional)'],
                    'status' => ['type' => 'string'],
                    'date_from' => ['type' => 'string', 'description' => 'Y-m-d (optional if invoice_query is set)'],
                    'date_to' => ['type' => 'string', 'description' => 'Y-m-d'],
                    'limit' => ['type' => 'integer'],
                ],
            ])),
            $this->fn('get_sales_invoice_detail', 'Return **header + line items** for one Sales Invoice. Use for “detail invoice”, “baris faktur”, line items. Pass invoice_no (or invoice_id). Searches all active company entities.', $props([
                'type' => 'object',
                'properties' => [
                    'invoice_no' => ['type' => 'string', 'description' => 'Sales invoice number'],
                    'invoice_id' => ['type' => 'integer', 'description' => 'Internal id if known'],
                ],
            ])),
            $this->fn('search_purchase_orders', 'Search purchase orders by supplier name/code fragment, optional status, and date range.', $props([
                'type' => 'object',
                'properties' => [
                    'supplier_query' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                    'date_from' => ['type' => 'string'],
                    'date_to' => ['type' => 'string'],
                    'limit' => ['type' => 'integer'],
                ],
            ])),
            $this->fn('search_delivery_orders', 'Search delivery orders by customer and planned delivery date range.', $props([
                'type' => 'object',
                'properties' => [
                    'customer_query' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                    'date_from' => ['type' => 'string'],
                    'date_to' => ['type' => 'string'],
                    'limit' => ['type' => 'integer'],
                ],
            ])),
            $this->fn('search_goods_receipt_po', 'Search goods receipt (GRPO) documents by supplier and date.', $props([
                'type' => 'object',
                'properties' => [
                    'supplier_query' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                    'date_from' => ['type' => 'string'],
                    'date_to' => ['type' => 'string'],
                    'limit' => ['type' => 'integer'],
                ],
            ])),
            $this->fn('search_inventory_items', 'Search inventory items by name/code, category name fragment, optional warehouse id, optional low_stock_only.', $props([
                'type' => 'object',
                'properties' => [
                    'name_query' => ['type' => 'string'],
                    'category' => ['type' => 'string'],
                    'warehouse_id' => ['type' => 'integer'],
                    'low_stock_only' => ['type' => 'boolean'],
                    'limit' => ['type' => 'integer'],
                ],
            ])),
            $this->fn('search_business_partners', 'Search customers and/or suppliers by name or code.', $props([
                'type' => 'object',
                'properties' => [
                    'name_query' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['customer', 'supplier', 'both'], 'description' => 'Partner type filter'],
                    'limit' => ['type' => 'integer'],
                ],
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array{type: string, function: array{name: string, parameters: array<string, mixed>}}
     */
    private function fn(string $name, string $description, array $parameters): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => $parameters,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function summarizeToolResult(array $result): string
    {
        if (isset($result['error'])) {
            return 'Error: '.(string) $result['error'];
        }
        if (isset($result['header'], $result['lines']) && is_array($result['lines'])) {
            $no = is_array($result['header']) && isset($result['header']['invoice_no'])
                ? (string) $result['header']['invoice_no']
                : 'invoice';

            return count($result['lines']).' line(s) for '.$no.'.';
        }
        if (isset($result['rows']) && is_array($result['rows'])) {
            $n = count($result['rows']);

            return $n.' record(s) returned.';
        }

        return 'Data retrieved.';
    }
}
