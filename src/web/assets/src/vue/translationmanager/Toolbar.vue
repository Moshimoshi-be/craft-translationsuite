<template>
  <button
      v-show="selectedCount"
      @click="deleteSelectedTranslations()"
      type="button"
      class="btn menubtn a-button--action"
      data-icon="trash"
      title="Actions"
      tabindex="0">
  </button>
  <div class="flex flex-grow texticon search icon clearable">
    <input v-model="filter" @keyup="filterResults" type="text" class="text fullwidth" autocomplete="off" placeholder="Search" aria-label="Search">
    <div class="clear hidden" title="Clear" aria-label="Clear"></div>
  </div>
  <button @click="updateTranslations" v-show="selectedCategory != 'add'" class="btn submit">Save</button>
</template>

<script lang="ts">

import {defineComponent } from 'vue';
import { mapState, mapMutations } from "vuex";

export default defineComponent({
  data: () => ({

  }),
  computed: {
    filter: {
      get() {
        return this.$store.state.filter;
      },
      set(value) {
        return this.$store.dispatch('setFilter', value.toLowerCase());
      }
    },
    ...mapState([
        'translations',
        'filteredTranslations',
        'selectedCount',
        'selectedCategory'
    ])
  },
  methods: {
    filterResults(e) {
      // Let's filter out the modifiers.
      // There's a lot more but hey
      if (
          e.keyCode == 16 || // Shift
          e.keyCode == 17 || // Control
          e.keyCode == 18 || // Option
          e.keyCode == 20 || // Capslock
          e.keyCode == 27 || // Escape
          e.keyCode == 37 || // Arrow left
          e.keyCode == 38 || // Arrow up
          e.keyCode == 39 || // Arrow right
          e.keyCode == 40 || // Arrow down
          e.keycode == 91 // Meta?
      ) {
        return;
      }
      console.log(this.filter)
      let filtered = [];
      console.log(this.translations);
      filtered = this.translations.filter(translation => {
        // Let's check for the translations
        const languages = translation.languages;

        // Does the message include part of the filter
        const message = translation.message.toLowerCase();
        if (message.includes(this.filter)) {
          return true
        }

        // Maybe the translations?
        for (const [language, source] of Object.entries(languages)){

          if (source.db && source.db.toLowerCase().includes(this.filter)) {
            return true;
          }
          if (source.file && !source.db && source.file.toLowerCase().includes(this.filter)) {
            return true;
          }
        }

        // Nothing found
        return false;
      });

      this.$store.commit('filterTranslations', filtered);
    },
    deleteSelectedTranslations() {
      this.$store.commit('deleteSelectedTranslations');
    },
    updateTranslations() {
      this.$store.commit('updateChangedTranslations');
    }
  },
  mounted() {
    console.log("Loaded");
  },
});
</script>
