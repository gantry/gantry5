var title = content.elements.content.find('[data-collection-title]'),
    titleEdit = content.elements.content.find('[data-title-edit]'),
    titleValue;

if (title && titleEdit) {
    titleEdit.on('click', function() {
        title.attribute('contenteditable', 'true');
        title[0].focus();

        var range = document.createRange(), selection;
        range.selectNodeContents(title[0]);
        selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);

        titleValue = trim(title.text());
    });

    title.on('keydown', function(event) {

        switch (event.keyCode) {
            case 13: // return
            case 27: // esc
                event.stopPropagation();
                if (event.keyCode == 27) {
                    title.text(titleValue);
                }

                title.attribute('contenteditable', null);
                window.getSelection().removeAllRanges();
                title[0].blur();

                return false;
            default:
                return true;
        }
    }).on('blur', function(){
        title.attribute('contenteditable', null);
        title.data('collection-title', trim(title.text()));
        window.getSelection().removeAllRanges();
    });
}

module.exports = {
    colorpicker: require('./colorpicker'),
    fonts: require('./fonts'),
    menu: require('./menu'),
    icons: require('./icons'),
    filemanager: require('./filemanager')
};