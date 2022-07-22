<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;

final class DocsPresenter extends BasePresenter
{
    /* @var \Nette\Bridges\ApplicationLatte\TemplateFactory */
    private TemplateFactory $templateFactory;
    
    private static $docsLayout = 
        [
            'overview' => [
                'name' => 'Overview',
                'subpages' => [
                    'home' => 'Home',
                    'releasenotes' => 'Release notes',
                    'license' => 'License',
                    'faq' => 'FAQ'
                ]
            ],
            'api' => [
                'name' => 'API',
                'subpages' => [
                    'overview' => 'Overview',
                    'libraryinterface' => 'Library interface',
                    'interface' => 'Object model and interface',
                    'deviceevent' => 'Device event',
                    'time' => 'Time and time segments',
                    'entities' => 'Entities',
                    'filter' => '<i class="bi bi-caret-right"></i>&nbsp;Filter',
                    'model' => '<i class="bi bi-caret-right"></i>&nbsp;Model',
                    'discretemodel' => '&nbsp;&nbsp;<i class="bi bi-caret-right"></i>&nbsp;Discrete model',
                    'signalmodel' => '&nbsp;&nbsp;<i class="bi bi-caret-right"></i>&nbsp;Signal model',
                    'metric' => '<i class="bi bi-caret-right"></i>&nbsp;Metric',
                    'solver' => '<i class="bi bi-caret-right"></i>&nbsp;Solver',
                    'approximator' => '<i class="bi bi-caret-right"></i>&nbsp;Approximator',
                    'signal' => '<i class="bi bi-caret-right"></i>&nbsp;Signal',
                    'inspection' => 'Inspection interface',
                    'feedback' => 'Feedback',
                    'simpleinterface' => '"Simple" interface',
                    'configuration' => 'Configuration',
                ]
            ],
            'entities' => [
                'name' => 'Existing entities',
                'subpages' => [
                    'signalgenerator' => 'Signal generator filter',
                    'databasefilters' => 'Database reader &amp; writer filter',
                    'dbformat' => '<i class="bi bi-caret-right"></i>&nbsp;Database structure',
                    'calculatedsignal' => 'Calculated signal filter',
                    'errormetric' => 'Error metric filter',
                    'logfilters' => 'Log &amp; log replay filter',
                    'drawingfilter' => 'Drawing filter (legacy)',
                    'feedbacksender' => 'Feedback sender filter',
                    'signalfilters' => 'Mapping and masking filters',
                    'miscfilters' => 'Other filters',
                    'metrics' => 'Metrics',
                    'solvers' => 'Solvers',
                    'approximators' => 'Approximators',
                    
                ]
            ],
            'sdk' => [
                'name' => 'C++ SDK',
                'subpages' => [
                    'overview' => 'Overview',
                    'structure' => 'SDK structure',
                    'helpers' => 'Helpers',
                    'configuration' => '<i class="bi bi-caret-right"></i>&nbsp;Configuration',
                    'execution' => '<i class="bi bi-caret-right"></i>&nbsp;Execution',
                    'optimizer' => '<i class="bi bi-caret-right"></i>&nbsp;Optimizing parameters',
                ]
            ],
            'tools' => [
                'name' => 'Tools',
                'subpages' => [
                    'frontends' => 'Frontends',
                    'console' => '<i class="bi bi-caret-right"></i>&nbsp;Console',
                    'gui' => '<i class="bi bi-caret-right"></i>&nbsp;GUI',
                ]
            ]
        ];
    
    public function __construct(TemplateFactory $templateFactory) {
        parent::__construct("docs");
        
        $this->templateFactory = $templateFactory;
    }
    
    public function startup() {
        parent::startup();
        
        $this->template->subpageName = "";
        $this->template->docsLayout = self::$docsLayout;
        $this->template->addFunction('sectionExists', function($page, $subpage) { return file_exists(__DIR__.'/templates/Docs/pages/'.$page.'/'.$subpage.'.latte'); });
    }
    
    public function actionDefault($page = 'overview', $subpage = 'home')
    {
        $this->template->pageName = $page;
        $this->template->subpageName = $subpage;
        
        $tplFileName = __DIR__.'/templates/Docs/pages/'.$page.'/'.$subpage.'.latte';
        
        if (!file_exists($tplFileName))
            $tplFileName = __DIR__.'/templates/Docs/pages/missing.latte';
        
        $template = $this->templateFactory->createTemplate();
        $template->setFile($tplFileName);
        $template->addFunction('doclink', function($page, $subpage, $title) { return "<a href=\"".$this->link("Docs:", ['page' => $page, 'subpage' => $subpage])."\">".$title."</a>"; });
        $this->template->docContents = $template->renderToString();
    }
}
