<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use OpenCodeModeling\CodeGenerator;
use OpenCodeModeling\CodeGenerator\Config\Component;
use OpenCodeModeling\CodeGenerator\Transformator;

final class WorkflowConfigFactory
{
    public const SLOT_COMMAND_PATH = 'event_engine-command_path';
    public const SLOT_EVENT_PATH = 'event_engine-command_path';
    public const SLOT_AGGREGATE_PATH = 'event_engine-aggregate_path';
    public const SLOT_AGGREGATE_STATE_PATH = 'event_engine-aggregate_state_path';

    public const SLOT_EE_API_COMMAND_FILENAME = 'event_engine-ee_api_command_filename';
    public const SLOT_EE_API_EVENT_FILENAME = 'event_engine-ee_api_event_filename';
    public const SLOT_EE_API_AGGREGATE_FILENAME = 'event_engine-ee_api_aggregate_filename';

    public const SLOT_EE_API_COMMAND_FILE = 'event_engine-ee_api_command_file';
    public const SLOT_EE_API_EVENT_FILE = 'event_engine-ee_api_event_file';
    public const SLOT_EE_API_AGGREGATE_FILE = 'event_engine-ee_api_aggregate_file';

    public const SLOT_AGGREGATE_STATE = 'event_engine-aggregate_state';
    public const SLOT_AGGREGATE_BEHAVIOUR = 'event_engine-aggregate_behaviour';

    public const SLOT_COMMAND = 'event_engine-command';
    public const SLOT_EVENT = 'event_engine-event';

    /**
     * Configures the workflow for event engine prototype flavour with common options.
     *
     * @param CodeGenerator\Workflow\WorkflowContext $workflowContext
     * @param string $inputSlotEventSourcingAnalyzer
     * @param string $domainModelPath Path to save domain models like aggregates
     * @param string $apiDescriptionPath Path to save Event Engine descriptions e.g. command, event, aggregate
     * @param callable $filterConstName
     * @param callable $filterConstValue
     * @param callable $filterDirectoryToNamespace
     * @return Component
     */
    public static function prototypeConfig(
        CodeGenerator\Workflow\WorkflowContext $workflowContext,
        string $inputSlotEventSourcingAnalyzer,
        string $domainModelPath,
        string $apiDescriptionPath,
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace
    ): Component {
        $eeAggregateStateFactory = AggregateStateFactory::withDefaultConfig(
            $filterConstName,
            $filterConstValue,
            $filterDirectoryToNamespace
        );
        $eeAggregateBehaviourFactory = AggregateBehaviourFactory::withDefaultConfig(
            $filterConstName,
            $filterConstValue,
            $filterDirectoryToNamespace,
            $eeAggregateStateFactory->config()
        );
        $eeAggregateDescriptionFactory = AggregateDescriptionFactory::withDefaultConfig(
            $filterConstName,
            $filterConstValue,
            $filterDirectoryToNamespace
        );
        $eeCommandDescriptionFactory = CommandDescriptionFactory::withDefaultConfig(
            $filterConstName,
            $filterConstValue
        );
        $eeEventDescriptionFactory = EventDescriptionFactory::withDefaultConfig(
            $filterConstName,
            $filterConstValue
        );
        $eeDescriptionFileMethodFactory = DescriptionFileMethodFactory::withDefaultConfig();
        $phpEmptyClassFactory = EmptyClassFactory::withDefaultConfig($filterDirectoryToNamespace);

        $workflowContext->put(self::SLOT_AGGREGATE_PATH, $domainModelPath);
        $workflowContext->put(self::SLOT_AGGREGATE_STATE_PATH, $domainModelPath);
        $workflowContext->put(self::SLOT_EE_API_COMMAND_FILENAME, $apiDescriptionPath . DIRECTORY_SEPARATOR . 'Command.php');
        $workflowContext->put(self::SLOT_EE_API_EVENT_FILENAME, $apiDescriptionPath . DIRECTORY_SEPARATOR . 'Event.php');
        $workflowContext->put(self::SLOT_EE_API_AGGREGATE_FILENAME, $apiDescriptionPath . DIRECTORY_SEPARATOR . 'Aggregate.php');

        $componentDescription = [
            // Configure Event Engine description file generation
            $phpEmptyClassFactory->workflowComponentDescription(
                self::SLOT_EE_API_COMMAND_FILENAME,
                self::SLOT_EE_API_COMMAND_FILE
            ),
            $phpEmptyClassFactory->workflowComponentDescription(
                self::SLOT_EE_API_EVENT_FILENAME,
                self::SLOT_EE_API_EVENT_FILE
            ),
            $phpEmptyClassFactory->workflowComponentDescription(
                self::SLOT_EE_API_AGGREGATE_FILENAME,
                self::SLOT_EE_API_AGGREGATE_FILE
            ),
            // Configure Event Engine description method generation
            $eeDescriptionFileMethodFactory->workflowComponentDescription(
                self::SLOT_EE_API_COMMAND_FILE,
                self::SLOT_EE_API_COMMAND_FILE
            ),
            $eeDescriptionFileMethodFactory->workflowComponentDescription(
                self::SLOT_EE_API_EVENT_FILE,
                self::SLOT_EE_API_EVENT_FILE
            ),
            $eeDescriptionFileMethodFactory->workflowComponentDescription(
                self::SLOT_EE_API_AGGREGATE_FILE,
                self::SLOT_EE_API_AGGREGATE_FILE
            ),
            // Configure Event Engine description generation
            $eeCommandDescriptionFactory->workflowComponentDescription(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_EE_API_COMMAND_FILE,
                self::SLOT_EE_API_COMMAND_FILE
            ),
            $eeEventDescriptionFactory->workflowComponentDescription(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_EE_API_EVENT_FILE,
                self::SLOT_EE_API_EVENT_FILE
            ),
            $eeAggregateDescriptionFactory->workflowComponentDescription(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_EE_API_AGGREGATE_FILE,
                self::SLOT_AGGREGATE_PATH,
                self::SLOT_EE_API_AGGREGATE_FILE
            ),
            // Configure Event Engine aggregate state generation
            $eeAggregateStateFactory->workflowComponentDescriptionFile(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_AGGREGATE_STATE_PATH,
                self::SLOT_AGGREGATE_STATE
            ),
            // Configure Event Engine aggregate behaviour generation
            $eeAggregateBehaviourFactory->workflowComponentDescriptionFile(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_AGGREGATE_PATH,
                self::SLOT_AGGREGATE_STATE_PATH,
                self::SLOT_EE_API_EVENT_FILENAME,
                self::SLOT_AGGREGATE_BEHAVIOUR
            ),
            $eeAggregateBehaviourFactory->workflowComponentDescriptionCommandMethod(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_AGGREGATE_BEHAVIOUR,
                self::SLOT_AGGREGATE_BEHAVIOUR
            ),
            $eeAggregateBehaviourFactory->workflowComponentDescriptionEventMethod(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_AGGREGATE_BEHAVIOUR,
                self::SLOT_AGGREGATE_BEHAVIOUR
            ),
            $eeAggregateStateFactory->workflowComponentDescriptionModifyMethod(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_AGGREGATE_STATE,
                self::SLOT_AGGREGATE_STATE
            ),
            $eeAggregateStateFactory->workflowComponentDescriptionImmutableRecordOverride(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_AGGREGATE_STATE,
                self::SLOT_AGGREGATE_STATE
            ),
        ];

        return new CodeGenerator\Config\ArrayConfig(...$componentDescription);
    }

    /**
     * Configures the workflow for event engine functional flavour with common options.
     *
     * @param CodeGenerator\Workflow\WorkflowContext $workflowContext
     * @param string $inputSlotEventSourcingAnalyzer
     * @param string $commandPath
     * @param callable $filterConstName
     * @param callable $filterConstValue
     * @param callable $filterDirectoryToNamespace
     * @return Component
     */
    public static function functionalConfig(
        CodeGenerator\Workflow\WorkflowContext $workflowContext,
        string $inputSlotEventSourcingAnalyzer,
        string $commandPath,
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace
    ): Component {
        $eeCommandFactory = CommandFactory::withDefaultConfig(
            $filterConstName,
            $filterConstValue,
            $filterDirectoryToNamespace
        );

        $eeEventFactory = EventFactory::withDefaultConfig(
            $filterConstName,
            $filterConstValue,
            $filterDirectoryToNamespace
        );

        $workflowContext->put(self::SLOT_COMMAND_PATH, $commandPath);

        $componentDescription = [
            // Configure Event Engine command generation
            $eeCommandFactory->workflowComponentDescriptionFile(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_COMMAND_PATH,
                self::SLOT_COMMAND
            ),
            $eeEventFactory->workflowComponentDescriptionFile(
                $inputSlotEventSourcingAnalyzer,
                self::SLOT_EVENT_PATH,
                self::SLOT_EVENT
            ),
        ];

        return new CodeGenerator\Config\ArrayConfig(...$componentDescription);
    }

    /**
     * Configures a workflow to save the generated code of prototypeConfig() to files.
     *
     * @return Component
     */
    public static function codeToFilesForPrototypeConfig(): Component
    {
        $stringToFile = new Transformator\StringToFile();

        return new CodeGenerator\Config\ArrayConfig(
            Transformator\StringToFile::workflowComponentDescription(self::SLOT_EE_API_COMMAND_FILE, self::SLOT_EE_API_COMMAND_FILENAME),
            Transformator\StringToFile::workflowComponentDescription(self::SLOT_EE_API_EVENT_FILE, self::SLOT_EE_API_EVENT_FILENAME),
            Transformator\StringToFile::workflowComponentDescription(self::SLOT_EE_API_AGGREGATE_FILE, self::SLOT_EE_API_AGGREGATE_FILENAME),
            Transformator\CodeListToFiles::workflowComponentDescription($stringToFile, self::SLOT_AGGREGATE_BEHAVIOUR),
            Transformator\CodeListToFiles::workflowComponentDescription($stringToFile, self::SLOT_AGGREGATE_STATE),
        );
    }

    /**
     * Configures a workflow to save the generated code of prototypeConfig() to files.
     *
     * @return Component
     */
    public static function codeToFilesForFunctionalConfig(): Component
    {
        $stringToFile = new Transformator\StringToFile();

        return new CodeGenerator\Config\ArrayConfig(
            Transformator\CodeListToFiles::workflowComponentDescription($stringToFile, self::SLOT_COMMAND),
            Transformator\CodeListToFiles::workflowComponentDescription($stringToFile, self::SLOT_EVENT),
        );
    }
}
