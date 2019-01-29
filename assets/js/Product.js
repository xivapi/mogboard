import HeaderCategories from "./HeaderCategories";

class Product
{
    constructor()
    {
        this.uiButtons = $('.product .menu');
        this.uiTabs = $('.product .tab');
        this.uiCategory = $('.product .product-search-cat');
        this.uiAlertWindow = $('.alert-info-container');
        this.uiAlertButton = $('.btn-alert-info');
    }

    watch()
    {
        this.uiButtons.find('button').on('click', event => {
            const tab = $(event.currentTarget).attr('data-tab');

            this.switchTab(tab);
        });

        this.uiCategory.on('click', event => {
            const id = $(event.currentTarget).attr('data-cat');
            HeaderCategories.openCategory(id);
        });

        this.uiAlertWindow.on('click', event => {
            this.uiAlertWindow.removeClass('open');
        });

        this.uiAlertButton.on('click', event => {
            this.uiAlertWindow.addClass('open');
        });

        $(document).scroll(event => {
            let y = $(document).scrollTop(),
                menu = this.uiButtons;

            if (y >= 280) {
                menu.addClass('fixed');
            } else {
                menu.removeClass('fixed');
            }
        });
    }

    /**
     * Change product tab page
     */
    switchTab(tab)
    {
        // remove current active states
        this.uiButtons.find('.open').removeClass('open');
        this.uiTabs.find('.open').removeClass('open');

        // set active
        $(event.currentTarget).addClass('open');
        this.uiTabs.find(`.tab-${tab}`).addClass('open');
    }
}

export default new Product;
