<div class="panel">
    <h3><i class="icon-exchange"></i> {l s='Product Migration' mod='prestashopMigration'}</h3>
    
    <div class="panel-body">
        <form method="POST" class="form-horizontal">
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Database Configuration' mod='prestashopMigration'}</label>
                <div class="col-lg-9">
                    <p class="help-block">
                        {l s='Configure your source database in the module settings before proceeding with migration.' mod='prestashopMigration'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <div class="col-lg-9 col-lg-offset-3">
                    <button type="submit" name="submitMigration" class="btn btn-primary">
                        <i class="process-icon-refresh"></i>
                        {l s='Start Migration' mod='prestashopMigration'}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>