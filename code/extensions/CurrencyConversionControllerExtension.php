<?php
class CurrencyConversionControllerExtension extends Extension
{
    public function onBeforeInit()
    {
       CurrencyConversionRate::init_currency();
    }
}
