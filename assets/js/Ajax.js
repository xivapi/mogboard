class Ajax
{
    /**
     * Do a GET request
     */
    get(url, data, success, complete, error)
    {
        this.handleAjax('GET', url, data, success, complete, error);
    }

    /**
     * Do a POST request
     */
    post(url, data, success, complete, error)
    {
        this.handleAjax('GET', url, data, success, complete, error);
    }

    /**
     * Internal AJAX handler
     * @param method
     * @param url
     * @param data
     * @param success
     * @param complete
     * @param error
     */
    handleAjax(method, url, data, success, complete, error)
    {
        // send request
        $.ajax({
            url: url,
            type: method,
            data: data,
            success: success,
            complete: complete,
            error: (a,b,c) => {
                Popup.error('Error 37', 'Could not create list.');
                console.error(a,b,c);
                error(a,b,c);
            },
        });
    }
}

export default new Ajax;
