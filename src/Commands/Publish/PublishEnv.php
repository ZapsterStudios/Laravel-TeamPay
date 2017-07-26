<?php

namespace ZapsterStudios\Ally\Commands\Publish;

class PublishEnv extends Publisher
{
    /**
     * Construct the publishable command.
     *
     * @param  string  $command
     * @return void
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * Publish the required files.
     *
     * @return void
     */
    public function publish()
    {
        $this->append('.env', base_path('.env.example'));
        $this->append('.env', base_path('.env'));

        $this->notify('Publishing: Env File');
    }
}