import Modals from "./Modals";
import Popup from "./Popup";
import ButtonLoading from "./ButtonLoading";

class ProductAlerts
{
    constructor()
    {
        this.uiForm             = $('.create_alert_form');
        this.uiModal            = $('.alert_modal');
        this.uiModalButton      = $('.btn_create_alert');
        this.uiInfoModal        = $('.alert_info');
        this.uiInfoModalButton  = $('.btn_alert_info');
        this.uiExistingAlerts   = $('.existing_alerts');
    }

    watch()
    {
        // add modals
        Modals.add(this.uiModal, this.uiModalButton);
        Modals.add(this.uiInfoModal, this.uiInfoModalButton);

        // on submitting a new
        this.uiForm.on('submit', event => {
            event.preventDefault();

            const payload = {
                itemId: this.uiForm.find('#alert_item_id').val().trim(),
                name:   this.uiForm.find('#alert_name').val().trim(),
                option: this.uiForm.find('#alert_option').val().trim(),
                value:  this.uiForm.find('#alert_value').val().trim(),
                nq:     this.uiForm.find('#alert_nq').prop('checked'),
                hq:     this.uiForm.find('#alert_hq').prop('checked'),
                dc:     this.uiForm.find('#alert_dc').prop('checked'),
                email:  this.uiForm.find('#alert_notify_email').prop('checked'),
                discord:this.uiForm.find('#alert_notify_discord').prop('checked'),
            };

            this.createItemAlert(payload);
        });

        // deleting an alert
        $('html').on('click', '.btn_delete_alert', event => {
            const $btn = $(event.currentTarget);
            const url = $btn.attr('data-url');

            ButtonLoading.start($btn);

            $.ajax({
                url: url,
                type: 'GET',
                success: response => {
                    this.renderItemAlerts();
                    Popup.success('Alert Deleted', 'This alert has been deleted');
                },
                error: (a,b,c) => {
                    Popup.error('Error 42', 'Could not delete alert.');
                    console.log('--- ERROR ---');
                    console.log(a,b,c)
                },
                complete: () => {
                    ButtonLoading.finish($btn);
                }
            })
        })
    }

    createItemAlert(payload)
    {
        const $btn = this.uiForm.find('button.btn_create_alert');
        ButtonLoading.start($btn);

        // send request
        $.ajax({
            url: mog.urls.alerts.create,
            type: "POST",
            dataType: "json",
            data: JSON.stringify(payload),
            contentType: "application/json",
            success: response => {
                ButtonLoading.finish($btn);

                // if alert ok
                if (response) {
                    // load current alerts
                    this.renderItemAlerts();

                    // close modals
                    Modals.close(this.uiModal);

                    // todo - reset form

                    // confirm
                    Popup.success('Alert Created','Information on this alert will appear on the homepage!');
                    return;
                }

                // error
                Popup.success('Error',response.message);
            },
            error: (a,b,c) => {
                Popup.error('Error 37', 'Could not create alert.');
                console.log('--- ERROR ---');
                console.log(a,b,c)
            }
        });
    }

    renderItemAlerts()
    {
        $.ajax({
            url: mog.urls.alerts.renderForItem.replace('-id-', itemId),
            contentType: "application/json",
            success: response => {
                this.uiExistingAlerts.html(response);
            },
            error: (a,b,c) => {
                this.uiExistingAlerts.html('Could not obtain alerts for this item.');
                console.log('--- ERROR ---');
                console.log(a,b,c)
            }
        })
    }
}

export default new ProductAlerts;
