import HeaderCategories from './HeaderCategories';
import Http from './Http';

class HeaderCategoriesSelection
{
    constructor()
    {
        this.uiView = $('.search-ui');
        this.uiCatList = $('.search .categories');
    }

    watch()
    {
        this.uiCatList.find('button').on('click', event => {
            // hide any dropdowns
            HeaderCategories.hideDropdownMenus();

            // load category
            const catId = $(event.currentTarget).attr('id');

            Http.getItemCategoryList(catId, response => {
                console.log(response);
                this.uiView.html(response);
            });
        });
    }
}

export default new HeaderCategoriesSelection;
