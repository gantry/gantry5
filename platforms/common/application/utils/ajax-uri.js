var getGantryURI = function(view, method){
    if (!method) method = 'index';

    return GANTRY_AJAX_URL.replace(/\{view\}/, view).replace(/\{method\}/, method);
};

module.exports = getGantryURI;