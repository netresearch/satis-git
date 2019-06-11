<?php

namespace MBO\SatisGit\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

use MBO\RemoteGit\ProjectInterface;

class TestCase extends BaseTestCase {

    /**
     * Create a fake project with a given name
     *
     * @param string $projectName
     * @return ProjectInterface
     */
    protected function createMockProject($projectName){
        $project = $this->getMockBuilder(ProjectInterface::class)
            ->getMock()
        ;
        $project->expects($this->any())
            ->method('getName')
            ->willReturn($projectName)
        ;
        return $project;
    }

} 