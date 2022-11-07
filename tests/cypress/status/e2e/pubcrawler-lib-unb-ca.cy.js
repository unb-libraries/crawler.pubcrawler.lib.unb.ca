const host = 'https://pubcrawler.lib.unb.ca/cri/'
describe("UNB LIB Scopus Publications Crawler", ()=>{

  context('CRI Page', {baseUrl: host}, () => {
    beforeEach(() => {
      cy.visit('/')
      cy.title()
        .should('contain', 'CRI-affiliated publications')
    })

    specify('Past publications list should return 50+ results', () => {
      cy.get('#pastBtn')
        .click()
      cy.url()
        .should('match', /https:\/\/pubcrawler.lib.unb.ca\/cri\//)
      cy.get('li.pub-item')
        .should('have.lengthOf.at.least', 900)
    });
  })
})
