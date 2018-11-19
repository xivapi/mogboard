<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputArgument;

// Project name
set('application', 'mogboard');
set('repository', 'https://github.com/xivapi/mogboard');
set('ssh_multiplexing', false);
inventory('deploy-hosts.yml');
argument('composer', InputArgument::OPTIONAL, 'Run: Composer Update', false);

// --------------------------------------------------------

function result($text)
{
    $text = explode("\n", $text);

    foreach($text as $i => $t) {
        $text[$i] = "| ". $t;
    }

    writeln("|");
    writeln(implode("\n", $text));
    writeln("|");
    writeln("");
}

function deploy($config)
{
    writeln("------------------------------------------------------------------------------------");
    writeln("- Deploying {$config->name}");
    writeln("------------------------------------------------------------------------------------");

    // set directory
    cd($config->home);
    writeln('Checking authentication ...');

    //
    // Checkout branches and switch branch
    //
    run("git fetch");
    run("git checkout {$config->branch}");

    //
    // Reset any existing changes
    //
    $branchStatus = run('git status');
    if (stripos($branchStatus, 'Changes not staged for commit') !== false) {
        writeln('Changes on production detected, resetting git head.');
        $result = run('git reset --hard');
        result($result);
        $result = run('git status');
        result($result);
    }

    //
    // Pull latest changes
    //
    writeln('Pulling latest code from github ...');
    $result = run('git pull');
    result($result);
    writeln('Latest 10 commits:');
    $result = run('git log -10 --pretty=format:"%h - %an, %ar : %s"');
    result($result);

    // check some stuff
    $directory = run('ls -l');
    $doctrine  = run('test -e config/packages/doctrine.yaml && echo 1 || echo 0') === '1';

    //
    // Composer update
    //
    if (input()->getArgument('composer')) {
        if (stripos($directory, 'composer.json') !== false) {
            writeln('Updating composer libraries (it is normal for this to take a while)...');
            $result = run('composer update');
            result($result);
        }
    }


    //
    // Write version
    //
    writeln('Setting git version+hash');
    run('bash bin/version');

    //
    // Clear symfony cache
    //
    if (stripos($directory, 'symfony.lock') !== false) {
        writeln('Clearing symfony cache ...');
        $result = run('php bin/console cache:warmup') . "\n";
        $result .= run('php bin/console cache:clear') . "\n";
        $result .= run('php bin/console cache:clear --env=prod');
        result($result);

        //
        // Update database schema
        //
        if ($doctrine) {
            writeln('Updating database schema ...');

            // update db
            $result = run('php bin/console doctrine:schema:update --force --dump-sql');
            result($result);

            // ask if we should drop the current db
            /*
            $shouldDropDatabase = askConfirmation('(Symfony) Drop Database?', false);
            if ($shouldDropDatabase) {
                run('php bin/console doctrine:schema:drop --force');
            }
            */
        }
    }

    write("Deployed branch {$config->branch} to: {$config->name} environment\n\n");

    //
    // Announce on discord
    //
    //writeln('Posting update to discord');
    //run('php /home/dalamud/mog/bin/console ListCommitChangesCommand');
}

// --------------------------------------------------------

task('api', function () {
    deploy((Object)[
        'name'   => 'API',
        'home'   => "/home/dalamud/dalamud/",
        'branch' => 'master',
    ]);
})->onHosts('api');

task('staging', function () {
    deploy((Object)[
        'name'   => 'Staging',
        'home'   => "/home/dalamud/dalamud_staging/",
        'branch' => 'staging',
    ]);
})->onHosts('staging');

task('parser', function () {
    deploy((Object)[
        'name'   => 'Lodestone Parser',
        'home'   => "/home/dalamud/dalamud/",
        'branch' => 'master',
    ]);
})->onHosts('parser');
