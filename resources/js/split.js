import Split from 'split-grid';

export default function() {

    let split = undefined;
    let splitOptions = {};
    const grid = document.querySelector('.layout');
    const gutter = document.querySelector('.layout-gutter');
    const gutterWidth = 9; //px

    function screenLarge() {
        return window.innerWidth > 768;
    }

    function columnPercentage() {
        return (( 1 - gutterWidth/window.innerWidth) / 2 ) * 100 + '%';
    }

    function rowPercentage() {
        return (( 1 - gutterWidth/window.innerHeight) / 2 ) * 100 + '%';
    }

    function initSplit() {
        if(split) {
            split.destroy();
            grid.removeAttribute("style");
        };

        if(screenLarge()) {
            grid.classList.add('layout-columns');
            grid.style.gridTemplateColumns = `${columnPercentage()} ${gutterWidth}px ${columnPercentage()}`;
            splitOptions = {
                columnGutters: [{
                    track: 1,
                    element: gutter,
                }],
            }
        } 
        else {
            grid.classList.remove('layout-columns');
            grid.style.gridTemplateRows = `${rowPercentage()} ${gutterWidth}px ${rowPercentage()}`;
            splitOptions = {
                rowGutters: [{
                    track: 1,
                    element: gutter,
                }],
            }
        }

        split = Split({...splitOptions, minSize: 200 });
    }

    window.addEventListener('resize', () => {
        window.requestAnimationFrame( () => {
            if(grid.classList.contains('layout-columns') != screenLarge()) {
                initSplit();
            }
        });
    });

    initSplit();
}
