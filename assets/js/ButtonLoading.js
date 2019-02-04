class ButtonLoading
{
    start($ele)
    {
        const text = $ele.text();

        $ele.attr('data-text', text);

        $ele.addClass('btn_loading');
        $ele.prop('disabled', true);
        $ele.html('<i class="icon-load-c"></i>');
    }

    finish($ele)
    {
        const text = $ele.attr('data-text');

        $ele.removeClass('btn-loading');
        $ele.prop('disabled', false);
        $ele.html(text);
    }
}

export default new ButtonLoading;
