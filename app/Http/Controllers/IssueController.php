<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Respositories\IssueRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;

class IssueController extends Controller
{

    /**
     * @throws GuzzleException
     */
    public function index(Request $request)
    {
        $issue = Issue::where('issue_id', $request->issue['id'])->first();
        $issueRepo = new IssueRepository();
        $githubEvent = $request->header('X-GitHub-Event');

        if ($githubEvent == 'issues') {
            if ($request->json('action') == 'opened' && empty($issue)) {
                $queryParams = [];

                $firstList = $issueRepo->getFirstList();

                $queryParams = $issueRepo->loadTrelloParams($request, $firstList);

                $response = $issueRepo->post('/cards', ['query' => $queryParams]);

                if ($response->getStatusCode() == 200) {
                    $cardId = json_decode($response->getBody()->getContents());
                    Issue::create([
                        'issue_id' => $request->issue['id'],
                        'card_id' => $cardId->id
                    ]);
                }

                return $response;

            } else if (!empty($issue)) {
                if ($request->json('action') == 'closed') {
                    return $issueRepo->archiveCard($issue);
                } else if ($request->json('action') == 'reopened') {
                    return $issueRepo->reopenCard($issue);
                }  else if ($request->json('action') == 'deleted') {
                    return $issueRepo->deleteCard($issue);
                }
            }

            if ($request->json('action') == 'labeled') {
                $labels = $issueRepo->loadLabels($request);
            }

        }
    }

}
