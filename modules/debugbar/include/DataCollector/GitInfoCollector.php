<?php

namespace Lynxlab\ADA\Module\DebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class GitInfoCollector extends DataCollector implements Renderable
{
    private $rootdir;

    public function __construct($rootdir = '.')
    {
        $this->rootdir = rtrim($rootdir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'gitinfo';
    }

    /**
     * @return array
     */
    public function collect()
    {
        return [
            'branch' => $this->getCurrentGitBranch(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return [
            "branch" => [
                "icon" => "git",
                "tooltip" => "Latest SHA: " . substr($this->getCurrentGitCommit(), 0, 8),
                "map" => "gitinfo.branch",
                "default" => "",
            ],
        ];
    }

    /**
     * Get the hash of the current git HEAD
     * @param str $branch The git branch to check
     * @return mixed Either the hash or a boolean false
     */
    private function getCurrentGitCommit($branch = null)
    {
        $branch ??= $this->getCurrentGitBranch();
        if ($hash = file_get_contents(sprintf('%s.git/refs/heads/%s', $this->rootdir, $branch))) {
            return $hash;
        } else {
            return '';
        }
    }

    private function getCurrentGitBranch()
    {
        $ar = array_reverse(
            explode("/", file_get_contents($this->rootdir . '/.git/HEAD'))
        );
        return  trim($ar[0] ?? '');
    }
}
