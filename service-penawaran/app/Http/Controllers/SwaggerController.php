<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Penawaran Service API",
    version: "1.0.0",
    description: "Dokumentasi API untuk Service Penawaran"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local Docker Server"
)]
#[OA\SecurityScheme(
    type: "apiKey",
    in: "header",
    securityScheme: "ApiKeyAuth",
    name: "X-IAE-KEY"
)]
class SwaggerController extends Controller
{
}