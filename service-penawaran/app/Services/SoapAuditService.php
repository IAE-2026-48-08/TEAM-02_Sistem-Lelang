<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SoapAuditService
{
    private string $endpoint;
    private string $teamId;

    public function __construct()
    {
        $this->endpoint = env('SOAP_AUDIT_URL');
        $this->teamId   = 'TEAM-02';
    }

    public function auditBid(array $bidData, string $bearerToken): ?string
    {
        $xmlEnvelope = $this->buildEnvelope($bidData);

        Log::debug('[SOAP] Request XML', ['xml' => $xmlEnvelope]);

        $response = Http::withHeaders([
            'Content-Type'  => 'text/xml; charset=utf-8',
            'Authorization' => 'Bearer ' . $bearerToken,
        ])->send('POST', $this->endpoint, ['body' => $xmlEnvelope]);

        Log::debug('[SOAP] Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        if ($response->failed()) {
            Log::error('[SOAP] Audit gagal', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        $receiptNumber = $this->parseReceiptNumber($response->body());

        Log::info('[SOAP] Audit berhasil', ['receipt_number' => $receiptNumber]);

        return $receiptNumber;
    }

    private function buildEnvelope(array $data): string
    {
        $logContent = json_encode([
            'bid_id'     => $data['id'],
            'item_id'    => $data['item_id'],
            'user_id'    => $data['user_id'],
            'bid_amount' => $data['bid_amount'],
            'status'     => $data['status'] ?? 'winning',
            'audited_at' => now()->toIso8601String(),
        ]);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope
    xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:iae="http://iae.central/audit">
    <soap:Body>
        <iae:AuditRequest>
            <iae:TeamID>{$this->teamId}</iae:TeamID>
            <iae:ActivityName>BidPlaced</iae:ActivityName>
            <iae:LogContent><![CDATA[{$logContent}]]></iae:LogContent>
        </iae:AuditRequest>
    </soap:Body>
</soap:Envelope>
XML;
    }

    private function parseReceiptNumber(string $xmlResponse): ?string
    {
        libxml_use_internal_errors(true);

        $cleaned = preg_replace('/(<\/?)(\w+):/', '$1', $xmlResponse);
        $xml     = simplexml_load_string($cleaned);

        if ($xml === false) {
            Log::error('[SOAP] Gagal parse response XML', ['raw' => $xmlResponse]);
            return null;
        }

        $result = $xml->xpath('//ReceiptNumber');

        return $result ? (string) $result[0] : null;
    }
}