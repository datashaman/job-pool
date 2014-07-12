<?php

use Ulrichsg\Getopt\Getopt;

$getopt = new Getopt([
    # [ 'c', 'counter', Getopt::REQUIRED_ARGUMENT, '', 'links:counter' ],
]);

$getopt->parse();
