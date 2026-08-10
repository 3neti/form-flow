<?php

declare(strict_types=1);

use LBHurtado\FormFlowManager\Services\DriverService;

function settlementRailVoucher(mixed $rail): object
{
    return new class($rail)
    {
        public string $code = 'TEST-RAIL';

        public object $instructions;

        public object $owner;

        public function __construct(mixed $rail)
        {
            $this->instructions = (object) [
                'cash' => (object) [
                    'amount' => 750,
                    'currency' => 'PHP',
                    'settlement_rail' => $rail,
                    'validation' => (object) ['mobile' => null],
                ],
                'inputs' => (object) ['fields' => []],
                'rider' => (object) [],
            ];
            $this->owner = (object) ['name' => 'Issuer'];
        }
    };
}

function driverSettlementRailContext(object $voucher): array
{
    $driver = new class extends DriverService
    {
        public function context(object $voucher): array
        {
            return $this->buildContext($voucher);
        }
    };

    return $driver->context($voucher);
}

it('exposes the voucher settlement rail to the form-flow template context', function (): void {
    $context = driverSettlementRailContext(settlementRailVoucher((object) ['value' => 'PESONET']));

    expect($context['settlement_rail'])->toBe('PESONET')
        ->and($context['voucher']['instructions']['cash']['settlement_rail'])->toBe('PESONET');
});

it('keeps automatic settlement rail context empty for later server resolution', function (): void {
    $context = driverSettlementRailContext(settlementRailVoucher(null));

    expect($context['settlement_rail'])->toBe('')
        ->and($context['voucher']['instructions']['cash']['settlement_rail'])->toBe('');
});
