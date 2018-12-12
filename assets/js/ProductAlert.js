class ProductAlert
{
    constructor()
    {
        this.uiView = $('.product-alert-form');
        this.uiButton = $('.btn-add-alert');
    }

    watch()
    {
        this.uiButton.on('click', event => {
            this.uiView.toggleClass('open');
        });

        $(document).mouseup(event => {
            const buttons = this.uiButton;
            const nav = this.uiView;

            // if the target of the click isn't the container nor a descendant of the container
            if (!buttons.is(event.target) && buttons.has(event.target).length === 0
                && !nav.is(event.target) && nav.has(event.target).length === 0) {

                this.uiView.removeClass('open');
            }
        });
    }
}

export default new ProductAlert;
