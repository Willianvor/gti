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
        $requestObj = json_decode($request->payload);
        $issue = Issue::where('issue_id', $requestObj->issue->id)->first();
        $issueRepo = new IssueRepository();
        $githubEvent = $request->header('X-GitHub-Event');

        if ($githubEvent == 'issues') {
            if ($requestObj->action == 'opened' && empty($issue)) {
                $firstList = $issueRepo->getFirstList();
                $queryParams = $issueRepo->loadTrelloParams($requestObj, $firstList);
                $response = $issueRepo->post('/cards', $queryParams);

                if ($response->getStatusCode() == 200) {
                    $cardId = json_decode($response->getBody()->getContents());
                    Issue::create([
                        'issue_id' => $requestObj->issue->id,
                        'card_id' => $cardId->id
                    ]);
                }

                return $response;

            } else if (!empty($issue)) {
                if ($requestObj->action == 'closed') {
                    return $issueRepo->archiveCard($issue);
                } else if ($requestObj->action == 'reopened') {
                    return $issueRepo->reopenCard($issue);
                }  else if ($requestObj->action == 'deleted') {
                    return $issueRepo->deleteCard($issue);
                }
            }

            if ($requestObj->action == 'labeled') {
                $labels = $issueRepo->loadLabels($requestObj);
            }

        }
    }

}
