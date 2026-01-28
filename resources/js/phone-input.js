/**
 * International Phone Input Initialization
 *
 * This module imports intl-tel-input from npm and exposes it globally.
 * The library is used for phone number input with country codes and validation.
 *
 * Country name localization is handled by phone-country-data.js,
 * which provides Arabic translations and a locale-aware getter.
 */

import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/build/css/intlTelInput.css';
import { countryNamesAr, getLocalizedCountryNames } from './phone-country-data';

// Expose intlTelInput globally for blade templates
window.intlTelInput = intlTelInput;

// Expose country data globally for blade templates
window.phoneCountryNamesAr = countryNamesAr;
window.getLocalizedCountryNames = getLocalizedCountryNames;

// Lazy load utils.js for validation/formatting (it's a large file)
// This returns a promise that resolves to the utils module
window.loadIntlTelInputUtils = async () => {
    const utils = await import('intl-tel-input/build/js/utils.js');
    return utils;
};

export default intlTelInput;
