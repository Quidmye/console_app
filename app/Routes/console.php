<?php
return [
    'list {--page} --trademark' => [
        'action' => 'app\Actions\Parser\TestParser@parse',
        'where' => [
            'page' => 'number',
            'trademark' => '.{1,}'
        ]
    ],
    'help' => [
        'action' => 'app\Actions\Parser\TestParser@help'
    ]
];