<template>
  <nav>
    <ul>
      <li>
        <a href="#" @click.prevent="selectCategory({category: 'missing', refresh: true})" :class="{'sel': selectedCategory == 'missing'}">
          <span class="label">Missing Translations</span>
          <span class="badge">{{ countMissingTranslations }}</span>
        </a>
      </li>
      <li>
        <a href="#" @click.prevent="selectCategory({category: 'add'})" :class="{'sel': selectedCategory == 'add'}">
          <span class="label">Add translation</span>
        </a>
      </li>

      <li class="heading">
        <span>Categories</span>
      </li>

      <li v-for="(category, index) in categories" :key="index">
        <a href="#" @click.prevent="selectCategory({category: category, refresh: true})" :class="{'sel': selectedCategory == category}">
          <span class="label">{{ $filters.ucFirst(category) }}</span>
        </a>
      </li>
    </ul>
  </nav>
</template>

<script lang="ts">

import {defineComponent} from 'vue';
import {mapState, mapGetters, mapMutations} from 'vuex';
import axios from 'axios';

export default defineComponent({
  data: () => ({}),
  methods: {
    uppercaseFirst(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    },
    ...mapMutations([
        'selectCategory'
    ])
  },
  computed: {
    ...mapState([
      'categories',
      'languages',
      'missingTranslations',
      'countMissingTranslations',
        'selectedCategory'
    ]),
    ...mapGetters([
      'getSelectedCategory'
    ])
  },
  mounted() {
    console.log(this.categories);
  },
});
</script>
