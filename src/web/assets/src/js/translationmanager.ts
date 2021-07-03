import {createApp, defineComponent, defineAsyncComponent} from 'vue';
import {createStore} from "vuex";
import Toolbar from "@/vue/translationmanager/Toolbar.vue";
import Sidebar from "@/vue/translationmanager/Sidebar.vue";
import Main from "@/vue/translationmanager/Main.vue";
import Pagination from "@/vue/translationmanager/Pagination.vue";
import axios from "axios";

const store = createStore({
    state() {
        return {
            categories: [],
            selectedCategory: 'missing',
            countMissingTranslations: 0,
            missingTranslations: [],
            translations: [],
            filteredTranslations: [],
            paginatorLimit: 10,
            paginatorStep: 10,
            paginatorOffset: 0,
            languages: [],
            filter: '',
            hasSelected: false,
            selectedCount: 0,
            isLoading: false, // Used for showing a spinner in the main window when something is loading.
        }
    },
    getters: {
        getSelectedCategory(state) {
            return state.countMissingTranslations > 0 ? 'missing' : state.categories[0];
        }
    },
    mutations: {
        setInitialSelectedCategory(state) {
            if (state.countMissingTranslations > 0) {
                state.selectedCategory = 'missing';
            } else {
                state.selectedCategory = state.categories[0];
            }
        },
        setCategories(state, value) {
            state.categories = value;
        },
        setLanguages(state, value) {
            state.languages = value;
        },
        setFilter(state, value) {
            console.log("Set filter to", value);
            state.filter = value;
        },
        setMissingTranslations(state, value) {
            console.log("SETTING TRANSLATIONS", value)
            state.translations = value;
            state.filteredTranslations = state.translations;
            state.countMissingTranslations = value.length;
        },
        incrementMissingTranslations(state) {
            state.countMissingTranslations++;
        },
        setTranslations(state, value) {
            state.translations = value;
            state.filteredTranslations = value;
        },
        setSelectedCount(state, value) {
          state.selectedCount = value;
          console.log(state.selectedCount)
        },
        incrementSelectedCount(state) {
            state.selectedCount++;
            console.log(state.selectedCount);
        },
        decrementSelectedCount(state) {
            state.selectedCount--;
            console.log(state.selectedCount);
        },
        toggleHasSelected(state) {
          state.hasSelected = !state.hasSelected;
        },
        selectCategory(state, value) {
            // Check if there are changes to the translations first
            const changes = state.translations.filter(translation => translation.changed);
            if (changes.length) {
                if (!confirm("You have unsaved translations, do you want to discard them?")) {
                    return;
                }
            }

            const refresh = value.refresh;
            const category = value.category;
            state.selectedCategory = category;
            state.paginatorOffset = 0;
            state.paginatorLimit = state.paginatorStep;

            // Reset stuff
            state.filter = '';
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.classList.remove('checked');
            }

            if (refresh) {
                Promise.resolve(store.dispatch('fetchTranslations', category)).then(translations => {
                   state.translations = translations;
                   state.filteredTranslations = translations;
                   if (category == 'missing') {
                       state.countMissingTranslations = state.translations.length;
                   }
                });
            }
        },
        filterTranslations(state, filtered) {
            state.filteredTranslations = filtered;
        },
        nextPageOfTranslations(state) {
            if (state.paginatorOffset + state.paginatorLimit > state.filteredTranslations.length) {
                return false;
            }
            state.paginatorOffset += state.paginatorStep;
            state.paginatorLimit = state.paginatorOffset + state.paginatorStep;
            console.log("next page", state.paginatorOffset, state.paginatorLimit);
        },
        previousPageOfTranslations(state) {
            if (state.paginatorOffset == 0) {
                return false;
            }
            state.paginatorOffset -= state.paginatorStep;
            state.paginatorLimit = state.paginatorOffset + state.paginatorStep;
            console.log("prev page", state.paginatorOffset, state.paginatorLimit)
        },
        deleteSelectedTranslations(state) {
            const selectedTranslations = state.filteredTranslations.filter(translation => translation.selected == 1);
            const notSelectedTranslations = state.filteredTranslations.filter(translation => translation.selected == 0);

            if (state.selectedCategory == 'missing') {
                state.countMissingTranslations = notSelectedTranslations.length;
            }
            const data = {
                translations: selectedTranslations
            };

            axios.post("/admin/translationsuite/translations/delete-translations", data).then(response => {
                state.translations = state.filteredTranslations = state.translations.filter(function(e) {
                    return this.indexOf(e) < 0;
                }, selectedTranslations);
            });
        },
        updateChangedTranslations(state) {
            const changedTranslations = state.translations.filter(translation => translation.changed);
            console.log(changedTranslations);
            const data = {
              translations: changedTranslations,
            };
            axios.post("/admin/translationsuite/translations/update-translations", data).then(response => {
                for (let translation of changedTranslations) {
                    translation.changed = false;
                }
            }).catch(error => {
                console.error(error)
            });
        }
    },
    actions: {
        setFilter: ({commit, state}, value) => {
            commit('setFilter', value)
            return state.filter;
        },
        async fetchTranslations(state, category) {
            let request = await axios.get("/admin/translationsuite/translations/get-translations/" + category);
            return request.data;
        },
    }
});

const app = createApp(defineComponent({
    components: {
        'toolbar': Toolbar,
        'sidebar': Sidebar,
        'manager': Main,
        'pagination': Pagination,
    },
    data: () => ({}),
    methods: {
        async getCategories() {
            let request = await axios.get("/admin/translationsuite/translations/get-categories");
            return request.data
        },
        async getLanguages() {
            let request = await axios.get("/admin/translationsuite/translations/get-languages");
            return request.data;
        }
    },
    mounted() {
        Promise.all([this.getCategories(), this.getLanguages(), store.dispatch('fetchTranslations', 'missing')]).then(results => {
            store.commit('setCategories', results[0]);
            store.commit('setLanguages', results[1]);
            store.commit('setMissingTranslations', results[2]);

            if (store.state.countMissingTranslations > 0) {
                store.commit('selectCategory', {category: 'missing'});
            } else {
                store.commit('selectCategory', {category: store.state.categories[0], refresh: true});
            }
        });

        document.addEventListener('keydown', ev => {
            const cmdDown = ev.metaKey || ev.ctrlKey;
            const saveKey = ev.key == "s"

            if (cmdDown && saveKey) {
                ev.preventDefault();
                store.commit('updateChangedTranslations');
            }
        });
    }
}));
app.config.globalProperties.$filters = {
    ucFirst(value: string) {
        return value.charAt(0).toUpperCase() + value.slice(1);
    }
}
app.use(store);
app.mount('#main');

