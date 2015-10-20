<?php

namespace vanare\BehatJunitFormatter\Renderer;

use vanare\BehatJunitFormatter\Formatter\FormatterInterface;
use vanare\BehatJunitFormatter\Node;
use vanare\BehatJunitFormatter\Renderer\RendererInterface;

class JsonRenderer implements RendererInterface
{
    /**
     * @var FormatterInterface
     */
    protected $formatter;

    /**
     * @var array
     */
    protected $result = [];

    /**
     * @param FormatterInterface $formatter
     */
    public function __construct(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     */
    public function render()
    {
        $suites = $this->formatter->getSuites();

        foreach ($suites as $suite) {
            array_push($this->result, $this->processSuite($suite));
        }
    }

    /**
     * @param bool|true $asString
     *
     * @return array|string
     */
    public function getResult($asString = true)
    {
        if ($asString) {
            return json_encode(array_pop($this->result));
        }

        return $this->result;
    }

    /**
     * @param Node\Suite $suite
     *
     * @return array
     */
    protected function processSuite(Node\Suite $suite)
    {
        $currentSuite = [];

        foreach ($suite->getFeatures() as $feature) {
            array_push($currentSuite, $this->processFeature($feature));
        }

        return $currentSuite;
    }

    /**
     * @param Node\Feature $feature
     *
     * @return array
     */
    protected function processFeature(Node\Feature $feature)
    {
        $currentFeature = [
            'uri' => $feature->getUri(),
            'id' => $feature->getId(),
            'tags' => $feature->getTags() ? $this->processTags($feature->getTags()) : [],
            'keyword' => $feature->getKeyword(),
            'name' => $feature->getName(),
            'line' => $feature->getLine(),
            'description' => $feature->getDescription(),
            'elements' => [],
        ];

        foreach ($feature->getScenarios() as $scenario) {
            array_push($currentFeature['elements'], $this->processScenario($scenario));
        }

        return $currentFeature;
    }

    /**
     * @param Node\Scenario $scenario
     *
     * @return array
     */
    protected function processScenario(Node\Scenario $scenario)
    {
        $currentScenario = [
            'id' => $scenario->getId(),
            'tags' => $scenario->getTags() ? $this->processTags($scenario->getTags()) : [],
            'keyword' => $scenario->getKeyword(),
            'name' => $scenario->getName(),
            'line' => $scenario->getLine(),
            'description' => $scenario->getDescription(),
            'type' => $scenario->getType(),
            'steps' => [],
            'examples' => [],
        ];

        foreach ($scenario->getSteps() as $step) {
            array_push($currentScenario['steps'], $this->processStep($step));
        }

        foreach ($scenario->getExamples() as $example) {
            array_push($currentScenario['examples'], $this->processExample($example));
        }

        return $currentScenario;
    }

    /**
     * @param Node\Step $step
     *
     * @return array
     */
    protected function processStep(Node\Step $step)
    {
        $result = [
            'keyword' => $step->getKeyword(),
            'name' => $step->getName(),
            'line' => $step->getLine(),
            'match' => $step->getMatch(),
            'result' => $step->getProcessedResult(),
        ];

        if (count($step->getEmbeddings())) {
            $result['embeddings'] = $step->getEmbeddings();
        }

        return $result;
    }

    /**
     * @param Node\Example $example
     *
     * @return array
     */
    protected function processExample(Node\Example $example)
    {
        $currentExample = [
            'keyword' => $example->getKeyword(),
            'name' => $example->getName(),
            'line' => $example->getLine(),
            'description' => $example->getDescription(),
            'id' => $example->getId(),
            'rows' => [],
        ];

        foreach ($example->getRows() as $row) {
            array_push($currentExample['rows'], $this->processExampleRow($row));
        }

        return $currentExample;
    }

    /**
     * @param Node\ExampleRow $exampleRow
     *
     * @return array
     */
    protected function processExampleRow(Node\ExampleRow $exampleRow)
    {
        return [
            'cells' => $exampleRow->getCells(),
            'id' => $exampleRow->getId(),
            'line' => $exampleRow->getLine(),
        ];
    }

    /**
     * @param array $tags
     * @return array
     */
    public function processTags(array $tags)
    {
        $result = [];

        foreach ($tags as $tag) {
            $result[] = [
                'name' => sprintf('@%s', $tag),
            ];
        }

        return $result;
    }
}
