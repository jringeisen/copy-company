<?php

// Note: The CheckSesReputation command creates its own SesClient internally,
// making it difficult to test without actual AWS credentials or major refactoring.
// These tests verify the basic command structure.

test('command has correct signature', function () {
    $this->artisan('ses:check-reputation --help')
        ->expectsOutputToContain('Check SES sending reputation')
        ->assertExitCode(0);
});

test('command exists and can be listed', function () {
    $this->artisan('list')
        ->expectsOutputToContain('ses:check-reputation')
        ->assertExitCode(0);
});
