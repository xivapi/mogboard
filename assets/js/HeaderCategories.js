class HeaderCategories
{
    constructor()
    {
        this.uiCurrentOpenCatList = null;
        this.uiButtons = $('.search .search-bar');
        this.uiCatList = $('.search .categories');
    }

    watch()
    {
        this.uiButtons.on('click', 'button', event => {
            const id = $(event.currentTarget).attr('id');
            this.toggleDropdownMenu(id);
        });

        $(document).mouseup(event => {
            const buttons = this.uiButtons.find('button');
            const catlist = this.uiCatList.find('.open');

            // if the target of the click isn't the container nor a descendant of the container
            if (!buttons.is(event.target) && buttons.has(event.target).length === 0
                && !catlist.is(event.target) && catlist.has(event.target).length === 0) {
                this.uiButtons.find('.active').removeClass('active');
                this.uiCatList.find('.open').removeClass('open');
                this.uiCurrentOpenCatList = null;
            }
        });
    }

    toggleDropdownMenu(id)
    {
        this.hideDropdownMenus();

        // set states
        if (this.uiCurrentOpenCatList !== id) {
            this.uiButtons.find(`#${id}`).addClass('active');
            this.uiCatList.find(`.${id}`).addClass('open');
            this.uiCurrentOpenCatList = id;
        } else {
            this.uiCurrentOpenCatList = null;
        }
    }

    hideDropdownMenus()
    {
        // remove any previous states
        this.uiButtons.find('.active').removeClass('active');
        this.uiCatList.find('.open').removeClass('open');
        this.uiCurrentOpenCatList = null;
    }
}

export default new HeaderCategories;
