/**
 * International Phone Input Initialization
 *
 * This module imports intl-tel-input from npm and exposes it globally.
 * The library is used for phone number input with country codes and validation.
 */

import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/build/css/intlTelInput.css';

// Expose intlTelInput globally for blade templates
window.intlTelInput = intlTelInput;

// Lazy load utils.js for validation/formatting (it's a large file)
// This returns a promise that resolves to the utils module
window.loadIntlTelInputUtils = async () => {
    const utils = await import('intl-tel-input/build/js/utils.js');
    return utils;
};

export default intlTelInput;
