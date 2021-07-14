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

            ],
            selection: [
                { name: "Message", type: 'message' },
                { name: "Category", type: 'category' },
            ],
        }),
        computed: {
          selectionList() {
              return JSON.stringify(this.selection);
          }
        },
        methods: {
            log(event) {
                console.log(event);
            }
        },
        mounted() {
            // @ts-ignore
            const languages = window.Translationsuite.getActiveLanguages();
            for (let language of languages) {
                this.list.push(
                    {
                        name: language.toUpperCase(),
                        type: 'language'
                    }
                )
            }
        }
    }));

    const root = app.mount('#importContainer');
    return root;
};

// Execute async function
main().then( (root) => {
});
