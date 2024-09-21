<?php

namespace App\Http\Controllers\Api\ML;

use GuzzleHttp\Client;
use Illuminate\Routing\Controller;

class TrainingController extends Controller
{

    /**
     * Train ML models.
     */
    public function trainModels(): string
    {
        $client = new Client(['base_uri' => 'http://ml:5002/train']);

        $response = $client->post('/train', [
            'json' => ['data' => 'sample data']
        ]);

        $body = $response->getBody();
        return $body->getContents();
    }
}
