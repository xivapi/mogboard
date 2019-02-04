class Popup
{
    constructor()
    {
        this.icons = {
            success: 'xiv-SymbolCheck',
            error:   'xiv-SymbolCross',
            warning: 'xiv-SymbolAlert',
            info:    'xiv-SymbolQuestion',

        };

        this.ui = $('.popup');
        this.ui.on('click', event => {
            this.close();
        })
    }

    success(title, message)
    {
        this.setPopupIcon('success').setTitle(title).setMessage(message).open();
    }

    error(title, message)
    {
        this.setPopupIcon('error').setTitle(title).setMessage(message).open();
    }

    warning(title, message)
    {
        console.log('title = ' + title);
        this.setPopupIcon('warning').setTitle(title).setMessage(message).open();
    }

    info(title, message)
    {
        this.setPopupIcon('info').setTitle(title).setMessage(message).open();
    }

    setTitle(title)
    {
        this.ui.find('h1').html(title);
        return this;
    }

    setMessage(title)
    {
        this.ui.find('p').html(title);
        return this;
    }

    open()
    {
        this.ui.addClass('open');
        return this;
    }

    close()
    {
        this.ui.removeClass('open');
        return this;
    }

    setPopupIcon(type)
    {
        this.ui.find('.popup_icon').attr('data-type', type);
        this.ui.find('.popup_icon i').removeClass();
        this.ui.find('.popup_icon i').addClass(this.icons[type]);
        return this;
    }
}

export default new Popup;
