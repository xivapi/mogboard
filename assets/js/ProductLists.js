import Modals from "./Modals";
import Popup from "./Popup";
import ButtonLoading from "./ButtonLoading";
import Ajax from "./Ajax";

class ProductLists
{
    constructor()
    {
        this.uiForm             = $('.create_list_form');
        this.uiModal            = $('.list_modal');
        this.uiModalButton      = $('.btn_addto_list');
        this.uiFaveButton       = $('.btn_addto_fave');
    }

    watch()
    {
        // add modals
        Modals.add(this.uiModal, this.uiModalButton);

        // on submitting a new
        this.uiForm.on('submit', event => {
            event.preventDefault();

            const name   = this.uiForm.find('#list_name').val().trim();
            const itemId = this.uiForm.find('#list_item_id').val().trim();

            this.create(name, itemId);
        });

        // on fave clicking
        this.uiFaveButton.on('click', event => {
            this.addToFavourite();
        });
    }

    /**
     * Add an item to your favourites.
     */
    addToFavourite()
    {
        ButtonLoading.start(this.uiFaveButton);

        $.ajax({
            url: mog.urls.lists.favourite,
            method: 'POST',
            data: {
                itemId: itemId,
            },
            success: response => {
                response.state ? this.uiFaveButton.addClass('on') : this.uiFaveButton.removeClass('on');

                this.uiFaveButton.find('span').text(response.state ? 'Faved' : 'Favourite');
                Modals.close(this.uiModal);
                Popup.success(
                    response.state ? 'Added to Faves' : 'Removed from Faves',
                    response.state ? 'Why you love this item so much!? Added to your favourites.' : 'Unpopular item ey, removed from your favourites.'
                );
            },
            error: response => {
                Popup.error('Error 37', 'Could not add to favourites!');
                console.error(response);
                ButtonLoading.finish(this.uiFaveButton);
            }
        });
    }

    /**
     * Create a new list
     */
    create(name, itemId)
    {
        const $button = this.uiForm.find('button[type="submit"]');
        ButtonLoading.start($button);

        const data = {
            name: name,
            itemId: itemId
        };

        const success = response => {
            console.log(response);
        };

        const complete = () => {
            ButtonLoading.finish($button);
        };

        Ajax.post(mog.urls.lists.create, data, success, complete);
    }

    /**
     * Add an item to an existing list
     */
    addItem(listId, itemId)
    {
        // send request
        $.ajax({
            url: mog.urls.lists.addItem.replace('-id-', listId),
            type: "POST",
            data: {
                itemId: itemId
            },
            success: response => {
                console.log(response);
            },
            error: (a,b,c) => {
                Popup.error('Error 37', 'Could not add an item.');
                console.error(a,b,c);
            },
            complete: () => {
                ButtonLoading.finish($button);
            }
        });
    }
}

export default new ProductLists;
