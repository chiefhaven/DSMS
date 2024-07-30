<div class="tab-pane fade" id="search-photos" role="tabpanel" aria-labelledby="search-photos-tab">
    <div class="row">
      <form class="mb-5" id="invoiceSettings">
        <div class="row g-sm push">
          <div class="container">
            <div class="form-floating mb-4">
                <textarea class="form-control @error('header') is-invalid @enderror" id="header" name="header" style="height: 200px" value="" placeholder="Description here">{{$invoice_setting->header}}</textarea>
                <label class="form-label" for="example-textarea-floating">Header text</label>
            </div>
            <div class="form-group mb-4">
                <label for="example-ltf-password">Logo</label>
                <input type="file" class="form-control @error('invoice_logo') is-invalid @enderror" id="invoice_logo" name="invoice_logo" placeholder="logo">
            </div>
            <div class="form-floating mb-4">
                <input type="text" class="form-control @error('due') is-invalid @enderror" id="due" name="due" placeholder="due" value="{{$invoice_setting->invoice_due_days}}">
                <label class="form-label" for="example-email-input-floating">Default invoice due (days)</label>
            </div>
            <div class="form-floating mb-4">
                <input type="text" class="form-control @error('prefix') is-invalid @enderror" id="prefix" name="prefix" placeholder="prefix" value="{{$invoice_setting->prefix}}">
                <label class="form-label" for="example-email-input-floating">Invoice number prefix</label>
            </div>
          </div>
          <div class="mb-4">
            <div class="form-check">
              <label class="form-check-label" for="block-form8-remember-me">User year in invoice numbering</label>
              <input class="form-check-input" type="checkbox" value="" id="year" name="year" checked>
            </div>
          </div>
          <div class="form-floating mb-4">
              <textarea class="form-control @error('terms') is-invalid @enderror" id="terms" name="terms" style="height: 200px" value="" placeholder="Description here">{{$invoice_setting->terms}}</textarea>
              <label class="form-label" for="example-textarea-floating">Invoice terms</label>
          </div>
          <div class="form-floating mb-4">
              <textarea class="form-control @error('footer') is-invalid @enderror" id="footer" name="footer" style="height: 200px" value="" placeholder="Description here">{{$invoice_setting->footer}}</textarea>
              <label class="form-label" for="example-textarea-floating">Footer text</label>
          </div>
        </div>
        <br>
        <div class="form-group">
            <button class="btn btn-primary" id="invoicesettings-update">Update</button>
        </div>
      </form>
    </div>
  </div>
