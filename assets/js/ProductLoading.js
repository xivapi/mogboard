class ProductLoading
{
    constructor()
    {
        this.ui   = $('.loading-container');
        this.text = $('.product-loading-bar-text');
        this.bar  = $('.product-loading-bar span');
        this.active = false;
    }

    show()
    {
        this.active = true;
        this.ui.addClass('on');
        return this;
    }

    hide()
    {
        this.active = false;
        this.ui.removeClass('on');
        this.setInstant().removeLonger().set(0, '');

        setTimeout(() => {
            this.removeInstant();
            this.removeLonger();
        }, 50);

        return this;
    }

    set(percent, text)
    {
        this.text.html(text);
        this.bar.css('width', `${percent}%`);
        return this;
    }

    setText(text)
    {
        if (this.active) {
            this.text.html(text);
        }

        return this;
    }

    setLonger()
    {
        this.bar.addClass('longer');
        return this;
    }

    removeLonger()
    {
        this.bar.removeClass('longer');
        return this;
    }

    setInstant()
    {
        this.bar.addClass('instant');
        return this;
    }

    removeInstant()
    {
        this.bar.removeClass('instant');
        return this;
    }
}

export default new ProductLoading;
