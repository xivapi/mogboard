import HeaderCategories from "./HeaderCategories";

class ProductTabs
{
    constructor()
    {
        this.uiButtons = $('.product .menu');
        this.uiTabs = $('.product .tab');
        this.uiCategory = $('.product .product-search-cat');
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

export default new ProductTabs;
