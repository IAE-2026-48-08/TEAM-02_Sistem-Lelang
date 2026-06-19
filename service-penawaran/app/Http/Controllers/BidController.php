<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Services\AmqpPublisherService;
use App\Services\SoapAuditService;
use App\Services\SsoService;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Info(title: "Penawaran Service API", version: "1.0.0")]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    in: "header",
    name: "X-IAE-KEY"
)]
class BidController extends Controller
{
    use ApiResponser;

    public function __construct(
        private SsoService           $ssoService,
        private SoapAuditService     $soapAuditService,
        private AmqpPublisherService $amqpPublisher
    ) {}

    #[OA\Get(
        path: "/api/v1/bids",
        summary: "Mengambil daftar penawaran",
        security: [["ApiKeyAuth" => []]],
        responses: [new OA\Response(response: 200, description: "Berhasil")]
    )]
    public function index()
    {
        return $this->successResponse(Bid::all(), 'Daftar penawaran berhasil diambil.');
    }

    #[OA\Get(
        path: "/api/v1/bids/{id}",
        summary: "Mengambil data penawaran spesifik",
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Detail penawaran."),
            new OA\Response(response: 404, description: "Penawaran tidak ditemukan")
        ]
    )]
    public function show($id)
    {
        $bid = Bid::find($id);
        if (! $bid) {
            return $this->errorResponse('Penawaran tidak ditemukan', 404);
        }
        return $this->successResponse($bid, 'Detail penawaran.');
    }

    #[OA\Post(
        path: "/api/v1/bids",
        summary: "Mengajukan penawaran baru",
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["item_id", "bid_amount"],
                properties: [
                    new OA\Property(property: "item_id",    type: "string", example: "ITEM-001"),
                    new OA\Property(property: "bid_amount", type: "number", example: 150000),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: "Penawaran berhasil diajukan.")]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'item_id'    => 'required|string',
            'bid_amount' => 'required|numeric|min:1',
        ]);

        $ssoUser = $request->input('sso_user');
        $userId  = $ssoUser ? (string) $ssoUser->id : 'USR-' . rand(100, 999);

        $bid = Bid::create([
            'item_id'    => $request->input('item_id'),
            'user_id'    => $userId,
            'bid_amount' => $request->input('bid_amount'),
        ]);

        // Modul 2: SOAP Audit
        $receiptNumber = null;
        try {
            $m2mToken      = $this->ssoService->getM2MToken();
            $receiptNumber = $this->soapAuditService->auditBid($bid->toArray(), $m2mToken);

            if ($receiptNumber) {
                $bid->update(['soap_receipt_number' => $receiptNumber]);
            }
        } catch (\Exception $e) {
            Log::error('[SOAP] Gagal audit bid', ['error' => $e->getMessage(), 'bid_id' => $bid->id]);
        }

        // Modul 3: AMQP Publisher
        try {
            $m2mToken = $this->ssoService->getM2MToken();

            Log::debug('[AMQP] Token yang dipakai', ['token_preview' => substr($m2mToken, 0, 30) . '...']);

            $published = $this->amqpPublisher->publishViaHttp(
                'bid.placed',
                [
                    'event'      => 'bid.placed',
                    'service'    => 'penawaran-service',
                    'team_id'    => 'TEAM-02',
                    'bid_id'     => $bid->id,
                    'item_id'    => $bid->item_id,
                    'user_id'    => $bid->user_id,
                    'bid_amount' => $bid->bid_amount,
                    'timestamp'  => now()->toIso8601String(),
                ],
                $m2mToken
            );

            Log::info('[AMQP] Published: ' . ($published ? 'true' : 'false'));

        } catch (\Exception $e) {
            Log::error('[AMQP] Gagal publish event', [
                'error'  => $e->getMessage(),
                'bid_id' => $bid->id,
            ]);
}

        return $this->successResponse(
            array_merge($bid->fresh()->toArray(), ['soap_receipt_number' => $receiptNumber]),
            'Penawaran berhasil diajukan.',
            201
        );
    }
}