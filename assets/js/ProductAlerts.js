import Settings from "./Settings";
import Modals from "./Modals";
import Popup from "./Popup";
import ButtonLoading from "./ButtonLoading";

class ProductAlerts
{
    constructor()
    {
        this.maxTriggers = 15;

        this.newAlert = {
            alert_item_id: null,
            alert_name: null,
            alert_nq: null,
            alert_hq: null,
            alert_dc: null,
            alert_notify_discord: null,
            alert_notify_email: null,
            alert_triggers: [],
        };

        this.uiForm = $('.alert_form');
        this.uiTriggers = $('.alert_entries');
    }

    watch()
    {
        this.uiForm.on('click', '.alert_trigger_remove', event => { this.removeCustomTrigger(event) });
        this.uiForm.on('click', '.alert_trigger_add', event => { this.addCustomTrigger(event) });
        this.uiForm.on('click', '.btn_create_alert', event => { this.createNewAlert(event) });
    }

    /**
     * Add a new custom trigger to the alert form ui
     */
    addCustomTrigger(event)
    {
        event.preventDefault();

        if (this.newAlert.alert_triggers.length >= this.maxTriggers) {
            Popup.error('Max Triggers Reached', `You can add a maximum of ${this.maxTriggers} to a single alert. Sorry!`);
            return;
        }

        const trigger = {
            id: Math.floor((Math.random() * 99999) + 1),
            alert_trigger_field: this.uiForm.find('#alert_trigger_field').val().trim(),
            alert_trigger_op:    this.uiForm.find('#alert_trigger_op').val().trim(),
            alert_trigger_value: this.uiForm.find('#alert_trigger_value').val().trim(),
        };

        // check a trigger exists
        if (trigger.alert_trigger_value.length === 0) {
            Popup.error('Invalid Condition Value', 'The triggers condition value is empty.');
            return;
        }

        // check bool type
        if (['Prices_IsCrafted','Prices_IsHQ','Prices_HasMateria','History_IsHQ'].indexOf(trigger.alert_trigger_field) > -1) {
            if (['0','1'].indexOf(trigger.alert_trigger_value) === -1) {
                Popup.error('Invalid Condition Value', 'For the selected trigger field, <br> your trigger value must either be: <br> a 0 (False/No) OR a 1 (True/Yes).');
                return;
            }

            if (['5','6'].indexOf(trigger.alert_trigger_op) === -1) {
                Popup.error('Invalid Operator', 'For the selected trigger field, <br> your trigger operator must be either: <br> = Equal-to OR != Not equal-to')
                return;
            }
        }

        // store
        this.newAlert.alert_triggers.push(trigger);

        // print trigger visual
        this.uiTriggers.append(`
            <div data-id="${trigger.id}">
                <div><button type="button" class="alert_trigger_remove small"><i class="xiv-NavigationClose"></i></button></div>
                <div>
                    <code>
                        <span>${trigger.alert_trigger_field}</span>
                        <em>${alert_trigger_operators[trigger.alert_trigger_op]}:</em>
                        <strong>${trigger.alert_trigger_value}</strong>
                    </code>
                </div>
                <span class="fr">${this.newAlert.alert_triggers.length}</span>
            </div>
        `);
    }

    /**
     * Create a new alert!
     * @param event
     */
    createNewAlert(event)
    {
        event.preventDefault();
    }

    /**
     * Remove custom triggers
     * @param event
     */
    removeCustomTrigger(event)
    {
        // todo - remove logic
    }
}

export default new ProductAlerts;
