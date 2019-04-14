<?php

namespace KanbanBoard;

use Github\Client;
use Github\HttpClient\CachedHttpClient;

class Github
{
    const CACHE_DIR = '/tmp/github-api-cache';

    private $client;
    private $milestone_api;
    private $account;

    /**
     * Github constructor.
     *
     * @param $token
     * @param $account
     */
    public function __construct($token, $account)
    {
        $this->account = $account;
        $this->client = new Client(new CachedHttpClient(array('cache_dir' => self::CACHE_DIR)));
        $this->client->authenticate($token, Client::AUTH_HTTP_TOKEN, Client::AUTH_URL_CLIENT_ID);
        $this->milestone_api = $this->client->api('issues')->milestones();
    }

    /**
     * @param string $repository
     *
     * @return array list of all project milestones
     */
    public function milestones(string $repository): array
    {
        return $this->milestone_api->all($this->account, $repository);
    }


    /**
     * @param string $repository
     * @param int    $milestone_id
     *
     * @return array list of issues found
     */
    public function issues(string $repository, int $milestone_id): array
    {
        $issue_parameters = array('milestone' => $milestone_id, 'state' => 'all');
        return $this->client->api('issue')->all($this->account, $repository, $issue_parameters);
    }
}