import HeaderCategories from "./HeaderCategories";
import Popup from "./Popup";
import ButtonLoading from "./ButtonLoading";

class Product
{
    constructor()
    {
        this.uiButtons = $('.product .menu');
        this.uiTabs = $('.product .tab');
        this.uiCategory = $('.product .product-search-cat');
        this.uiRefreshButton = $('.btn_request_update');
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

            if (y >= 300) {
                menu.addClass('fixed');
            } else {
                menu.removeClass('fixed');
            }
        });

        this.uiRefreshButton.on('click', event => {
            ButtonLoading.start(this.uiRefreshButton);

            $.ajax({
                url: mog.url_item_refresh.replace('-id-', itemId),
                success: response => {
                    Popup.success('Prioritised!', 'This item has been bumped to the front of the queue. Check back in a minute and the prices/history should have been updated.');
                    ButtonLoading.disable(this.uiRefreshButton, 'Queued');
                    this.uiRefreshButton.addClass('btn-green-outline').removeClass('btn-green')
                },
                error: (a,b,c) => {
                    Popup.error('Error 37', 'Could not request item pricing refresh');
                    console.log('--- ERROR ---');
                    console.log(a,b,c)
                }
            })
        });
    }

    /**
     * Change product tab page
     */
    switchTab(tab)
    {
        // remove current active states
        this.uiButtons.find('button.open').removeClass('open');
        this.uiTabs.find('.tab-page.open').removeClass('open');

        // set active
        $(event.currentTarget).addClass('open');
        this.uiTabs.find(`.tab-${tab}`).addClass('open');
    }
}

export default new Product;
