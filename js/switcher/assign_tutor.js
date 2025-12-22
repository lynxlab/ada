function initDoc() {
    $j('input[type="submit"]').addClass('ui button');
    $j('[id^=tooltip]').each((i, el) => {
        const target = $j(el).attr('id');
        if (target.indexOf('Content') == -1) {
            const id = parseInt(target.replace('tooltip', ''));
            const source = `tooltipContent${id}`;
            if ($j(`#${source}`).length > 0) {
                $j(`#${target}`).closest('label').after(`<i class="ui info icon ${id}"></i>`);
                $j(`#${source}`)
                .modal('setting', 'detachable', false)
                .modal('attach events', `.ui.info.icon.${id}`, 'show');
                // $j(`.ui.info.icon.${id}`).popup({
                //     target: el,
                //     content: $j(`#${source}`).html(),
                //     inline: true,
                //     on: 'click',
                // });
            }
        }
    });
}
