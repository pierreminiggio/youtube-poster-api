<?php

namespace App;

use DateTime;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class App
{

    public function __construct(
        private DatabaseFetcher $fetcher,
        private string $token,
        private ?string $proxy
    )
    {
    }

    public function run(
        string $path,
        ?string $queryParameters,
        ?string $authHeader
    ): void
    {
        if ($path === '/') {
            http_response_code(404);

            return;
        }

        if (! $authHeader || $authHeader !== 'Bearer ' . $this->token) {
            http_response_code(401);

            return;
        }

        
        $youtubeChannelId = substr($path, 1);

        $fetchedAccounts = $this->fetcher->query(
            $this->fetcher->createQuery(
                'youtube_account'
            )->select(
                'google_login',
                'google_password',
                'google_recovery_email'
            )->where('channel_id = :channel_id'),
            ['channel_id' => $youtubeChannelId]
        );

        if (empty($fetchedAccounts)) {
            http_response_code(404);

            return;
        }

        $body = file_get_contents('php://input');

        if (! $body) {
            http_response_code(400);

            return;
        }

        $jsonBody = json_decode($body, true);

        if (! $jsonBody) {
            http_response_code(400);

            return;
        }

        if (empty($jsonBody['video_url']) || empty($jsonBody['title']) || empty($jsonBody['description'])) {
            http_response_code(400);

            return;
        }

        $projectFolder = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        $cacheFolder = $projectFolder . 'cache' . DIRECTORY_SEPARATOR;
        if (! file_exists($cacheFolder)) {
            mkdir($cacheFolder);
        }

        $videoUrl = $jsonBody['video_url'];

        $videoFileName = $cacheFolder . (new DateTime())->getTimestamp() . '.mp4';

        set_time_limit(0);

        $fp = fopen($videoFileName, 'w+');
        $ch = curl_init($videoUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        $fetchedAccount = $fetchedAccounts[0];
        $googleLogin = $fetchedAccount['google_login'];
        $googlePassword = $fetchedAccount['google_password'];
        $googleRecoveryEmail = $fetchedAccount['google_recovery_email'];

        $output = trim(shell_exec(
            'LC_CTYPE=en_US.utf8 node '
            . $projectFolder
            . 'post.js '
            . escapeshellarg($googleLogin)
            . ' '
            . escapeshellarg($googlePassword)
            . ' '
            . escapeshellarg($googleRecoveryEmail)
            . ' '
            . escapeshellarg($videoFileName)
            . ' '
            . escapeshellarg($jsonBody['title'])
            . ' '
            . escapeshellarg($jsonBody['description'])
            . (
                $this->proxy !== null
                ? ' ' . escapeshellarg($this->proxy)
                : ''
            )
            . ' 2>&1'
        ));

        unlink($videoFileName);

        if (empty($output)) {
            http_response_code(500);
            echo json_encode(['error' => 'Empty output']);

            return;
        }

        $jsonOutput = json_decode($output, true);

        if (empty($jsonOutput)) {
            http_response_code(500);
            echo json_encode(['error' => 'Empty JSON output']);

            return;
        }
        
        $isAssoc = function (array $arr) {
            if (array() === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        }
        
        if ($isAssoc($jsonOutput)) {
            http_response_code(500);
            echo json_encode(['error' => $output]);

            return;
        }

        $probablyLink = $jsonOutput[0];
        $youtubeLinkStart = 'https://youtu.be/';

        if (! str_starts_with($probablyLink, $youtubeLinkStart)) {
            http_response_code(500);
            echo json_encode(['error' => $output]);

            return;
        }

        $youtubeId = substr($probablyLink, strlen($youtubeLinkStart));

        if (empty($youtubeId)) {
            http_response_code(500);
            echo json_encode(['error' => 'Bad youtube link : ' . $probablyLink]);

            return;
        }

        http_response_code(200);
        echo json_encode(['id' => $youtubeId]);
    }
}
