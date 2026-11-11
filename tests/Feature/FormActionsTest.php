<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FormActionsTest extends TestCase
{
    private string $renderedHtml;

    public function test_page_cancel_route_names_exist(): void
    {
        $checked = 0;
        foreach (File::allFiles(resource_path('views')) as $file) {
            preg_match_all('/<x-form-actions\b[^>]*\broute\([\'"]([^\'"]+)[\'"]/', $file->getContents(), $matches);
            foreach ($matches[1] as $name) {
                $this->assertTrue(Route::has($name), $file->getRelativePathname().': '.$name);
                $checked++;
            }
        }
        $this->assertGreaterThan(0, $checked);
    }

    public function test_page_footer_has_cancel_then_save_with_matching_shapes(): void
    {
        $xpath = $this->render('<form><input name="example"><x-form-actions cancel-url="/students" /></form>');
        $footer = $xpath->query('//*[contains(@class, "form-actions")]')->item(0);
        $this->assertStringContainsString('justify-content-end', $footer->getAttribute('class'));
        $this->assertStringContainsString('border-top', $footer->getAttribute('class'));
        $this->assertStringContainsString('mt-4', $footer->getAttribute('class'));
        $buttons = $xpath->query('./a | ./button', $footer);
        $this->assertSame(2, $buttons->length);
        $this->assertSame('Cancel', trim($buttons->item(0)->textContent));
        $this->assertSame('/students', $buttons->item(0)->getAttribute('href'));
        $this->assertSame('Save', trim($buttons->item(1)->textContent));
        $this->assertSame('submit', $buttons->item(1)->getAttribute('type'));
        foreach ($buttons as $button) {
            $this->assertStringContainsString('rounded-pill px-4', $button->getAttribute('class'));
        }
    }

    public function test_modal_cancel_dismisses_without_submitting(): void
    {
        $xpath = $this->render('<x-form-actions modal />');
        $cancel = $xpath->query('//button')->item(0);
        $this->assertSame('button', $cancel->getAttribute('type'));
        $this->assertSame('modal', $cancel->getAttribute('data-bs-dismiss'));
        $this->assertSame(1, $xpath->query('//*[contains(@class, "modal-footer")]')->length);
        $this->assertSame(1, $xpath->query('//button[@type="submit"]')->length);
    }

    public function test_vue_handlers_and_loading_state_are_preserved(): void
    {
        $xpath = $this->render(<<<'BLADE'
<x-form-actions modal cancel-click="closeForm">
    <button type="button" class="btn btn-primary rounded-pill px-4" @click="saveForm" :disabled="isSaving">
        <span v-if="isSaving">Saving...</span><span v-else>Save</span>
    </button>
</x-form-actions>
BLADE);
        $buttons = $xpath->query('//button');
        $this->assertSame(2, $buttons->length);
        $this->assertStringContainsString('@click="closeForm"', $this->renderedHtml);
        $this->assertFalse($buttons->item(0)->hasAttribute('data-bs-dismiss'));
        $this->assertStringContainsString('@click="saveForm"', $this->renderedHtml);
        $this->assertStringContainsString(':disabled="isSaving"', $this->renderedHtml);
        $this->assertSame(1, $xpath->query('//span[@*[name()="v-if"]="isSaving"]')->length);
    }

    public function test_custom_cancel_slot_preserves_disabled_state_and_order(): void
    {
        $xpath = $this->render(<<<'BLADE'
<x-form-actions modal>
    <x-slot:cancel>
        <button type="button" class="btn btn-alt-secondary rounded-pill px-4" @click="cancelPayment" :disabled="isSaving">Cancel</button>
    </x-slot:cancel>
    <button type="submit" class="btn btn-primary rounded-pill px-4">Save</button>
</x-form-actions>
BLADE);
        $buttons = $xpath->query('//button');
        $this->assertSame(2, $buttons->length);
        $this->assertStringContainsString('@click="cancelPayment"', $this->renderedHtml);
        $this->assertStringContainsString(':disabled="isSaving"', $this->renderedHtml);
        $this->assertSame('submit', $buttons->item(1)->getAttribute('type'));
    }

    private function render(string $template): \DOMXPath
    {
        $this->renderedHtml = Blade::render($template);
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($this->renderedHtml);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return new \DOMXPath($document);
    }
}
