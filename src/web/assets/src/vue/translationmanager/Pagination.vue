<template>
  <div id="count-container" class="light flex-grow" v-if="selectedCategory != 'add'">
    <div class="flex pagination">
      <div @click="previousPageOfTranslations" class="page-link prev-page" :class="{'disabled': paginatorOffset == 0 }" title="Previous Page"></div>
      <div @click="nextPageOfTranslations" class="page-link next-page" :class="{'disabled': paginatorLimit > amountOfTranslations }" title="Next Page"></div>
      <div class="page-info">{{ paginatorOffset }}-{{ amountOfResults }} of {{ amountOfTranslations }} entries</div>
    </div>
  </div>
</template>

<script lang="ts">

import {defineComponent} from 'vue';
import {mapState, mapMutations} from "vuex";

export default defineComponent({
  data: () => {
    return {}
  },
  computed: {
    amountOfResults() {
      if(this.filteredTranslations.length < this.paginatorLimit) {
        return this.filteredTranslations.length;
      }

      return this.paginatorLimit;
    },
    amountOfTranslations() {
      return this.filteredTranslations.length
    },
    ...mapState([
      'filteredTranslations',
      'paginatorLimit',
      'paginatorOffset',
        'selectedCategory'
    ])
  },
  methods: {
    ...mapMutations([
        'nextPageOfTranslations',
        'previousPageOfTranslations'
    ])
  }
});

</script>