<?php

namespace Icinga\Module\Setup;

use Icinga\Application\Modules\Module;
use Icinga\Module\Setup\Requirement\ModuleMissingRequirement;
use Icinga\Module\Setup\Requirement\SetRequirement;
use Icinga\Module\Setup\Requirement\WebModuleRequirement;

class ModuleDependency
{
    /**
     * @var Module The given Module
     */
    protected $module;

    /**
     * @var array The chosen modules
     */
    protected $checkedModules;

    public function __construct(Module $module, array $checkedModules)
    {
        $this->module = $module;
        $this->checkedModules = $checkedModules;
    }

    /** Get the module dependency requirements
     *
     * @return RequirementSet
     */
    public function getRequirements()
    {
        $icingadbAndMonitoring = [];
        $set = new RequirementSet();

        foreach ($this->module->getRequiredModules() as $name => $requiredVersion) {
            if ($name === 'monitoring' || $name === 'icingadb') {
                $icingadbAndMonitoring[$name] = $requiredVersion;

                continue;
            }

            $options = [
                'alias'         => $name,
                'description'   => sprintf(
                    t('Module %s (%s) is required.'),
                    $name,
                    $requiredVersion
                )
            ];

            if (! in_array($name, $this->checkedModules)) {
                $set->add((new ModuleMissingRequirement($options)));
            } else {
                $options['condition'] = [$name, $requiredVersion];
                $set->add((new WebModuleRequirement($options)));
            }
        }

        if (! empty($icingadbAndMonitoring)) {
            $icingadbOrmonitoring = new RequirementSet(false, RequirementSet::MODE_OR);
            foreach ($icingadbAndMonitoring as $name => $requiredVersion) {
                $options = [
                    'alias'         => $name,
                    'optional'      => true,
                    'description'   => sprintf(
                        t('Module %s (%s) is required.'),
                        $name,
                        $requiredVersion
                    )
                ];

                if (! in_array($name, $this->checkedModules)) {
                    $icingadbOrmonitoring->add((new ModuleMissingRequirement($options)));
                } else {
                    $options['condition'] = [$name, $requiredVersion];
                    $icingadbOrmonitoring->add(new WebModuleRequirement($options));
                }
            }

            $set->merge($icingadbOrmonitoring);

            $requirement = (new SetRequirement([
                'title'         =>'Base Module',
                'alias'         => 'Monitoring OR Icingadb',
                'optional'      => false,
                'condition'     => $icingadbOrmonitoring,
                'description'   => t('Module Monitoring OR Icingadb is required.')
            ]));

            $set->add($requirement);
        }

        return $set;
    }
}