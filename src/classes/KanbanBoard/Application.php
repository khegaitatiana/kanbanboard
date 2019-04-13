<?php

namespace KanbanBoard;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;
use Utilities\Utilities;
use \Michelf\Markdown;

class Application
{

    const STATUS_ACTIVE    = "active";
    const STATUS_QUEUED    = "queued";
    const STATUS_PAUSED    = "paused";
    const STATUS_COMPLETED = "completed";
    const STATUS_CLOSED    = "closed";

    private $github;
    private $repositories;
    private $paused_labels;

    /**
     * Application constructor.
     *
     * @param array $paused_labels
     *
     * @throws \Exception
     */
    public function __construct(array $paused_labels = [])
    {
        try
        {
            $authentication = new Authentication();
            $token = $authentication->login();
            $this->github = new Github($token, Utilities::env('GH_ACCOUNT'));
            $this->repositories = explode('|', Utilities::env('GH_REPOSITORIES'));
            $this->paused_labels = $paused_labels;
        } catch (\Exception $e)
        {
            die($e->getMessage());
        }
    }

    /**
     * Render kanban board template
     * @return string
     */
    public function board(): string
    {
        $mustacheEngine = new Mustache_Engine(
            ['loader' => new Mustache_Loader_FilesystemLoader(ROOT . 'src/views')]);
        return $mustacheEngine->render('index', ['repositories' => $this->getRepositories()]);
    }

    /**
     * @return array
     */
    private function getRepositories(): array
    {
        $repositories = [];
        foreach ($this->repositories as $repository)
        {
            $repositories[] = ['repository' => $repository, 'milestones' => $this->getMilestones($repository)];
        }
        return $repositories;
    }

    /**
     * Get repository milestones - sorted by milestone name
     *
     * @param $repository
     *
     * @return array
     */
    private function getMilestones(string $repository): array
    {
        $milestones = [];
        foreach ($this->github->milestones($repository) as $data)
        {
            $issues = $this->getMilestoneIssues($repository, $data['number']);
            $percent = $this->getMilestoneProgress($data['closed_issues'], $data['open_issues']);
            if (!empty($percent))
            {
                $milestones[] = [
                    'milestone' => $data['title'],
                    'url'       => $data['html_url'],
                    'progress'  => $percent,
                    'queued'    => $this->getIssuesByStatus($issues, self::STATUS_QUEUED),
                    'active'    => $this->getIssuesByStatus($issues, self::STATUS_ACTIVE),
                    'completed' => $this->getIssuesByStatus($issues, self::STATUS_COMPLETED)
                ];
            }
        }

        usort($milestones, function($a, $b)
        {
            return strcmp($a["milestone"], $b["milestone"]);
        });

        return $milestones;
    }


    /**
     * @param string $repository
     * @param int    $milestone_id
     *
     * @return array
     */
    private function getMilestoneIssues(string $repository, int $milestone_id): array
    {
        $result = [];
        $issues = $this->github->issues($repository, $milestone_id);
        foreach ($issues as $issue)
        {
            if (isset($issue['pull_request']))
            {
                continue;
            }
            $result[$this->getIssueState($issue)][]
                = [
                'id'       => $issue['id'],
                'number'   => $issue['number'],
                'title'    => $issue['title'],
                'body'     => Markdown::defaultTransform($issue['body']),
                'url'      => $issue['html_url'],
                'assignee' => $this->getIssueAssignee($issue),
                'paused'   => $this->getMatchedLabels($issue, $this->paused_labels),
                'closed'   => $issue['closed_at']
            ];
        }
        if (Utilities::hasValue($result, self::STATUS_ACTIVE))
        {
            usort($result[self::STATUS_ACTIVE], function($a, $b)
            {
                return count($a['paused']) - count($b['paused']) ===
                       0 ? strcmp($a['title'], $b['title']) : 0;
            });
        }

        return $result;
    }

    /**
     * @param array $issue
     *
     * @return string
     */
    private function getIssueState(array $issue): string
    {
        if ($issue['state'] === self::STATUS_CLOSED)
        {
            return self::STATUS_COMPLETED;
        }
        else if (Utilities::hasValue($issue, 'assignee') && count($issue['assignee']) > 0)
        {
            return self::STATUS_ACTIVE;
        }
        else
        {
            return self::STATUS_QUEUED;
        }
    }

    /**
     * @param array $issue
     *
     * @return string|null
     */
    private function getIssueAssignee(array $issue): ?string
    {
        return Utilities::hasValue($issue, 'assignee') ? $issue['assignee']['avatar_url'] . '?s=16' : NULL;
    }

    /**
     * @param array $issue
     * @param array $needles
     *
     * @return array
     */
    private function getMatchedLabels(array $issue, array $needles): array
    {
        if (!empty($needles) && Utilities::hasValue($issue, 'labels'))
        {
            foreach ($issue['labels'] as $label)
            {
                if (in_array($label['name'], $needles))
                {
                    return [$label['name']];
                }
            }
        }

        return [];

    }

    /**
     * @param int $complete
     * @param int $remaining
     *
     * @return array
     */
    private function getMilestoneProgress(int $complete, int $remaining): array
    {
        $result = [];
        $total = $complete + $remaining;
        if ($total > 0)
        {
            $percent = ($complete OR $remaining) ? round($complete / $total * 100) : 0;
            $result = ['total'     => $total,
                       'complete'  => $complete,
                       'remaining' => $remaining,
                       'percent'   => $percent];
        }
        return $result;
    }

    /**
     * @param array  $issues
     * @param string $status
     *
     * @return array
     */
    private function getIssuesByStatus(array $issues, string $status): array
    {
        if (Utilities::hasValue($issues, $status))
        {
            return $issues[$status];
        }
        return [];
    }
}
