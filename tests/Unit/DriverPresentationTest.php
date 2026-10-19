<?php

use LBHurtado\FormFlowManager\Services\DriverService;
use LBHurtado\FormFlowManager\Services\FormFlowPresentationResolver;
use Symfony\Component\Yaml\Yaml;

it('transforms the root YAML presentation and keeps conditional progress accurate', function () {
    $driver = new class extends DriverService
    {
        public function configure(): void
        {
            $this->config = Yaml::parse(<<<'YAML'
reference_id: "{{ code }}"
presentation:
  schema: form-flow.presentation.v1
  brand:
    name: "{{ owner_name }}"
steps:
  absent:
    handler: form
    condition: "false"
    config: {}
  visible:
    handler: form
    fields:
      - name: name
        type: text
YAML);
        }

        protected function buildContext(object $voucher): array
        {
            return ['code' => 'SYNTHETIC', 'owner_name' => 'Synthetic Brand'];
        }
    };
    $driver->configure();
    $instructions = $driver->transform(new stdClass);
    $prepared = app(FormFlowPresentationResolver::class)->prepare($instructions);
    expect($prepared->presentation['brand']['name'])->toBe('Synthetic Brand')
        ->and($prepared->steps)->toHaveCount(1)
        ->and($prepared->steps[0]->config['presentation']['progress']['total'])->toBe(1);
});
