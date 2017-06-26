// helper function to set focus on a specificed input field, also sets cursor position to end of field content
function ixtheoSetFocus(input_selector) {
    $(input_selector).focus();
    if ($(input_selector).length) {
       // now we are sure that element exists
       // don't assign input_field earlier, JS might crash if element doesnt exist
       var input_field = $(input_selector);
       input_field[0].setSelectionRange(input_field.val().length, input_field.val().length);
    }
}

// function to register onload handler function
// multiple handlers can be chained, if necessary
function ixtheoRegisterOnLoad(functionOnLoad) {
    if(window.attachEvent) {
        window.attachEvent('onload', functionOnLoad);
    } else {
        if(window.onload) {
            var currentOnLoad = window.onload;
            var newOnLoad = function(evt) {
                currentOnLoad(evt);
                functionOnLoad(evt);
            };
            window.onload = newOnLoad;
        } else {
            window.onload = functionOnLoad;
        }
    }
}

// onload handler for ixtheo
function ixtheoOnLoad() {
    // advanced search: set focus on first input field of first search group
    if (window.location.href.match(/\/Search\/Advanced/i)) {
        ixtheoSetFocus('#search_lookfor0_0');
    // keywordchainsearch: set focus on 2nd input field
    } else if (window.location.href.match(/\/Keywordchainsearch\//i)) {
        ixtheoSetFocus('#kwc_input');
    // alphabrowse: set focus on "starting from" edit field
    } else if (window.location.href.match(/\/Alphabrowse\//i)) {
        ixtheoSetFocus('#alphaBrowseForm_from');
    }
}

ixtheoRegisterOnLoad(ixtheoOnLoad);
