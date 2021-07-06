<template>
  <div class="main">
    <div class="elements">
      <form class="flex flex-col justify-center items-start w-full xl:w-8/12" v-if="selectedCategory =='add'">
        <div class="flex flex-nowrap mb-16 w-full">
          <label for="category" class="inline-block mr-8 min-w-48">Choose a category:</label>
          <div class="select w-full">
            <select name="category" class="w-full" v-model="category">
              <option disabled value="">--- Select a category ---</option>
              <option v-for="category in categories" :value="category">{{ this.$filters.ucFirst(category) }}</option>
            </select>
          </div>
        </div>
        <div class="flex flex-nowrap mb-16 w-full">
          <label for="message" class="inline-block mr-8 min-w-48">Message:</label>
          <textarea name="message" type="text" rows="3" class="text w-full" v-model="message"/>
        </div>
        <div class="flex w-full justify-end mt-4">
          <input
              @click.prevent="addSourceMessage"
              :disabled="isFormDisabled"
              :class="{'disabled': isFormDisabled }"
              type="submit"
              class="btn submit px-6"
              value="Add"/>
        </div>

      </form>

      <div class="tableview tablepane" v-else>
        <table class="data fullwidth">
          <thead>
          <tr>
            <th @click="selectAll" class="checkbox-cell selectallcontainer orderable" role="checkbox" tabindex="0"
                aria-checked="false"
                aria-label="Select all">
              <div id="selectAll" class="checkbox"></div>
            </th>
            <th v-show="selectedCategory == 'missing'" scope="col" data-attribute="category">Category</th>
            <th scope="col" data-attribute="message">Message</th>
            <th v-for="language in languages" scope="col" :data-attribute="'language-' + language.locale">
              {{ language.name }}
            </th>
          </tr>
          </thead>

          <tbody>
          <tr v-for="(translation, index) in filteredTranslations.slice(paginatorOffset, paginatorLimit)"
              :key="selectedCategory == 'missing' ? 'missing' + '-' + index : translation.category + '-' + index">
            <td class="checkbox-cell">
              <div @click="selectOne(translation)" class="checkbox" :class="{'checked': translation.selected }"
                   title="Select" aria-label="Select"></div>
            </td>
            <td v-show="selectedCategory == 'missing'">
              <span class="title">{{ translation.category }}</span>
            </td>
            <td class="max-w-40">
              <span class="title">{{ translation.message }}</span>
            </td>
            <td v-for="(language, key) in translation.languages" :key="translation.category + '-' + index + '-' + key">
              <div class="a-translation-field">
                <textarea @keyup="markAsChanged(translation)" v-model="language.db"
                          :name="'translation-' + index + '-' + key" rows="1" autocomplete="off" class="text w-full"
                          :placeholder="language.file"></textarea>
                <label
                    :class="language.db ? 'type-db' : language.file ? 'type-file' : 'type-missing'">{{
                    language.db ? 'db' : language.file ? 'file' : 'missing'
                  }}</label>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>

import {defineComponent} from 'vue';
import {mapState} from 'vuex';
import axios from 'axios';

export default defineComponent({
  data: () => ({
    category: '',
    message: '',
    timeout: null,
  }),
  computed: {
    isFormDisabled() {
      return this.categories.length == 0 || !this.category || !this.message;
    },
    ...mapState([
      'translations',
      'filteredTranslations',
      'languages',
      'selectedCategory',
      'categories',
      'paginatorLimit',
      'paginatorOffset'
    ])
  },
  methods: {
    selectAll(e) {
      const selectAll = document.getElementById('selectAll');
      const checked = !selectAll.classList.contains('checked');

      selectAll.classList.toggle('checked');
      for (let translation of this.filteredTranslations) {
        translation.selected = checked;
      }
      if (checked) {
        this.$store.commit('setSelectedCount', this.filteredTranslations.length);
      } else {
        this.$store.commit('setSelectedCount', 0);
      }
    },
    selectOne(translation) {
      translation.selected = !translation.selected;
      if (translation.selected) {
        console.log("Incrementing")
        this.$store.commit('incrementSelectedCount');
      } else {
        console.log("Decrementing")
        this.$store.commit('decrementSelectedCount');
      }
    },
    addSourceMessage() {

      const data = {
        category: this.category,
        message: this.message,
      };

      axios.post('/admin/translationsuite/translations/add-source', data).then(result => {
        const data = result.data;

        if (data.message == 'success' && result.status === 200) {
          // It was added.
          this.category = '';
          this.message = '';
          this.$store.commit('incrementMissingTranslations');
          this.$toast.success("Added a new message");
        }
      });
    },
    markAsChanged(translation) {
      clearTimeout(this.timeout);
      this.timeout = setTimeout(_ => {
        translation.changed = true;
      }, 100);
    }
  }
});
</script>
