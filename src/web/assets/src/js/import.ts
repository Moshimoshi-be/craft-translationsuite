import { createApp, defineComponent, defineAsyncComponent } from 'vue';
import draggable from 'vuedraggable';

const main = async () => {
    const app = createApp(defineComponent({
        delimiters: ['${', '}'],
        components: {
            draggable: draggable,
        },
        data: () => ({
            enabled: true,
            dragging: false,
            list: [
                { name: "Message", id: 1 },
                { name: "Translation", id: 2 },
                { name: "Category", id: 3 },
                { name: "EN", id: 4 },
                { name: "ES", id: 5 },
                { name: "FR", id: 6 },
            ],
            selection: [

            ]
        }),
        methods: {
            log(event) {
                console.log(event);
            }
        }
    }));

    const root = app.mount('#importContainer');
    return root;
};

// Execute async function
main().then( (root) => {
});
