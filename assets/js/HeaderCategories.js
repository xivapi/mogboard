import Http from "./Http";

class HeaderCategories
{
    constructor()
    {
        this.uiButton = $('.btn-market-board');
        this.uiView = $('.market-board-container');
        this.uiCategory = $('.market-category-container');
        this.uiLazy = null;
        this.viewActive = false;
    }

    watch()
    {
        this.uiButton.on('click', event => {
            this.uiView.addClass('open');
        });

        this.uiView.find('button').on('click', event => {
            const id = $(event.currentTarget).attr('id');
            this.openCategory(id);
        });

        $(document).mouseup(event => {
            const btn = this.uiButton;
            const view = this.uiView;

            // if the target of the click isn't the container nor a descendant of the container
            if (!btn.is(event.target) && btn.has(event.target).length === 0
                && !view.is(event.target) && view.has(event.target).length === 0) {
                this.uiView.removeClass('open');
            }
        });

        $(document).mouseup(event => {
            const category = this.uiCategory;

            // if the target of the click isn't the container nor a descendant of the container
            if (!category.is(event.target) && category.has(event.target).length === 0) {
                this.uiCategory.removeClass('open');
                this.viewActive = false;
            }
        });

        this.uiCategory.find('.market-category').on('click', 'a', event => {
            this.uiCategory.find('.market-category').html('<div class="loading"><img src="/i/svg/loading2.svg"></div>');
        });
    }

    openCategory(id)
    {
        this.uiView.removeClass('open');
        this.uiCategory.addClass('open');

        this.uiCategory.find('.market-category').html('<div class="loading"><img src="/i/svg/loading2.svg"></div>');

        Http.getItemCategoryList(id, response => {
            this.uiCategory.find('.market-category').html(response);
            this.viewActive = true;
            this.setSearchHeight();

            this.uiLazy = $('.lazy').Lazy({
                // your configuration goes here
                scrollDirection: 'vertical',
                appendScroll: $('.item-category-list'),
                effect: 'fadeIn',
                visibleOnly: false,
                bind: 'event',
            });
        });
    }

    setSearchHeight()
    {
        if (this.viewActive) {
            // Handle height of search
            const $searchResults = $('.item-category-list');
            const windowHeight = Math.max(document.documentElement.clientHeight, window.innerHeight || 0) - 260;
            $searchResults.css({ height: `${windowHeight}px`} );
        }
    }

    setLoadingLazyLoadWatcher()
    {
        const el = new SimpleBar(document.getElementById('item-category-list'));
        el.getScrollElement().addEventListener('scroll', event => {
            this.uiLazy.data("plugin_lazy").update();
        });
    }
}

export default new HeaderCategories;
