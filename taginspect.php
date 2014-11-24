<?php
    function analyze(SplFileInfo $fileInfo) {
        if ($fileInfo->getExtension() === 'php') {
            $contents = file_get_contents($fileInfo->getRealPath());

            $phpOpening = preg_match('/^.+\<\?php/', $contents);
            if ($phpOpening) {
                echo "invalid_header " . $fileInfo->getFileName() . " " . $fileInfo->getRealPath() . "\n";
            }

            $noPhpClosing  = !preg_match('/\?\>(\r?\n)*$/',  $contents);
            $closingBlock  =  preg_match('/\}(\r?\n)*$/',   $contents);
            $closingParens =  preg_match('/\)\;(\r?\n)*$/', $contents);
            $closingArray  =  preg_match('/\]\;(\r?\n)*$/', $contents);
            $okEndOfFile   =  $noPhpClosing && ($closingBlock || $closingParens || $closingArray);
            
            if (!$okEndOfFile) {
                echo "invalid_footer " . $fileInfo->getFileName() . " " . $fileInfo->getRealPath() . "\n";
            }
        }
    }

    function allowed($path, $config) {
        if ($config !== NULL) {
            foreach ($config['excluded'] as $excludedItem) {
                if (preg_match('/' . preg_quote('/' . $excludedItem, '/') . '/', $path)) {
                    return FALSE;
                }
            }
        }

        return TRUE;
    }

    function detect($projectPath, $config) {
        $pathStack = new SplStack;
        $pathStack->push($projectPath);

        while (!$pathStack->isEmpty()) {
            $newPath = $pathStack->pop();

            if (allowed($newPath, $config)) {
                $dir = new DirectoryIterator($newPath);
                
                foreach ($dir as $fileInfo) {
                    if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                        $pathStack->push($fileInfo->getRealPath());
                    } else {
                        analyze($fileInfo);
                    }
                }
            }

        }
    };

    $projectPath = isset($argv[1]) ?
    	$argv[1] :
    	NULL;
    
    $configFile = isset($argv[2]) ?
    	$argv[2] :
    	NULL;

    $config = $configFile != NULL ?
    	json_decode(file_get_contents($configFile), TRUE) :
    	NULL;

    $argc = count($argv);
    if ($argc >= 2 && $argc <= 3 && $projectPath !== NULL) {
        detect($projectPath, $config);
    } else {
        file_put_contents('php://stderr', 'Usage: php taginspect.php <projectPath> <configFile>' . "\n");
    }
