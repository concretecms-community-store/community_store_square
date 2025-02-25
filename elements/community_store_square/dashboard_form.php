<?php defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>
<div class="form-group">
    <?=$form->label('squareCurrency',t('Currency'))?>
    <?=$form->select('squareCurrency',$squareCurrencies,$squareCurrency)?>
</div>

<div class="form-group">
    <?=$form->label('squareCountry',t('Country'))?>
    <?=$form->select('squareCountry',$squareCountryCodes ,$squareCountry)?>
</div>

<div class="form-group">
    <?=$form->label('squareMode',t('Mode'))?>
    <?=$form->select('squareMode',array('sandbox'=>t('Sandbox'), 'live'=>t('Live')),$squareMode)?>
</div>

<div class="row">
    <div class="col-md-6">

        <div class="form-group checkbox">
            <?= $form->label('squareEnableGooglePay',t('Enable Google Pay'))?>
            <?= $form->checkbox('squareEnableGooglePay', 1 ,$squareEnableGooglePay)?>
        </div>
    </div>
    <div class="col-md-6">

        <div class="form-group checkbox">
            <?= $form->label('squareEnableApplePay',t('Enable Apple Pay'))?>
            <?= $form->checkbox('squareEnableApplePay', 1 ,$squareEnableApplePay)?>
        </div>
    </div>

</div>

<div class="form-group">
    <?=$form->label('squareSandboxApplicationId',t('Sandbox Application ID'))?>
    <input type="text" name="squareSandboxApplicationId" value="<?=$squareSandboxApplicationId?>" class="form-control">
</div>

<div class="form-group">
    <?=$form->label('squareSandboxAccessToken',t('Sandbox Access Token'))?>
    <input type="text" name="squareSandboxAccessToken" value="<?=$squareSandboxAccessToken?>" class="form-control">
</div>

<div class="form-group">
    <?=$form->label('squareSandboxLocation',t('Sandbox Location'))?>
    <input type="text" name="squareSandboxLocation" value="<?=$squareSandboxLocation?>" class="form-control">
</div>

<div class="form-group">
    <?=$form->label('squareLiveApplicationId',t('Live Application ID'))?>
    <input type="text" name="squareLiveApplicationId" value="<?=$squareLiveApplicationId?>" class="form-control">
</div>

<div class="form-group">
    <?=$form->label('squareLiveAccessToken',t('Live Access Token'))?>
    <input type="text" name="squareLiveAccessToken" value="<?=$squareLiveAccessToken?>" class="form-control">
</div>

<div class="form-group">
    <?=$form->label('squareLiveLocation',t('Live Location'))?>
    <input type="text" name="squareLiveLocation" value="<?=$squareLiveLocation?>" class="form-control">
</div>
