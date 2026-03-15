import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
  app.component('index-grouped-checkbox', IndexField)
  app.component('detail-grouped-checkbox', DetailField)
  app.component('form-grouped-checkbox', FormField)
})
