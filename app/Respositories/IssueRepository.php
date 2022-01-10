<?php


namespace App\Respositories;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class IssueRepository
{
    private $client;
    private $queryKeyToken;
    private static $baseUrl = 'https://api.trello.com/1';

    public function __construct()
    {
        $this->client = new Client([
            'verify' => false
        ]);

        $this->queryKeyToken = [
            'key' => env('TRELLO_KEY'),
            'token' => env('TRELLO_TOKEN')
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function post(string $context, array $queryParams): ResponseInterface
    {
        return $this->client->post(self::$baseUrl.$context, ['query' => $queryParams]);
    }

    /**
     * @throws GuzzleException
     */
    public function getFirstList()
    {
        $lists = $this->client->get(self::$baseUrl."/board/2NLqMLZt/lists", ['query' => $this->queryKeyToken]);

        return json_decode($lists->getBody()->getContents())[0];
    }

    public function loadTrelloParams($request, $firstList): array
    {
        $trelloParams = [
            'name' => $request->issue->title,
            'pos' => 'top',
            'idList' => $firstList->id
        ];

        if ($request->issue->body) {
            $trelloParams['desc'] = $request->issue->body;
        }

        return $this->addQueryKeyToken($trelloParams);
    }

    public function addQueryKeyToken($queryParams): array
    {
        return array_merge($this->queryKeyToken, $queryParams);
    }

    /**
     * @throws GuzzleException
     */
    public function archiveCard($issue): int
    {
        $closeCard = ['closed' => true];
        $queryParams = $this->addQueryKeyToken($closeCard);
        $response = $this->client->put(self::$baseUrl."/cards/{$issue['card_id']}", ["query" => $queryParams]);
        return $response->getStatusCode();
    }

    /**
     * @throws GuzzleException
     */
    public function reopenCard($issue): int
    {
        $closeCard = ['closed' => false];
        $queryParams = $this->addQueryKeyToken($closeCard);
        $response = $this->client->put(self::$baseUrl."/cards/{$issue['card_id']}", ["query" => $queryParams]);
        return $response->getStatusCode();
    }

    /**
     * @throws GuzzleException
     */
    public function addNewComment($cardId, $issueId)
    {
        $queryComment['text'] = 'githubIdIssue' . $issueId;
        $queryParams = $this->addQueryKeyToken($queryComment);
        $this->client->post(self::$baseUrl."/cards/$cardId/actions/comments", ['query' => $queryParams]);
    }

    /**
     * @throws GuzzleException
     */
    public function loadLabels(): ResponseInterface
    {
        $labels = $this->client->get(self::$baseUrl."/board/2NLqMLZt/labels", ['query' => $this->queryKeyToken]);

        if (empty($labels)) {
            $x = 1;
        } else {
            $x = 2;
        }
    }

    /**
     * @throws GuzzleException
     */
    public function getLists(): ResponseInterface
    {
        return $this->client->get(self::$baseUrl."/board/2NLqMLZt/lists", ['query' => $this->queryKeyToken]);
    }

    /**
     * @throws GuzzleException
     */
    public function deleteCard($issue)
    {
        $response = $this->client->delete(self::$baseUrl."/cards/{$issue['card_id']}", ["query" => $this->queryKeyToken]);
        $issue->where('issue_id', $issue['issue_id'])->delete();
        return $response;
    }

}
