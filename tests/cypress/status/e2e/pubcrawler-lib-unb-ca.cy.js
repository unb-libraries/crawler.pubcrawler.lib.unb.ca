const host = 'https://pubcrawler.lib.unb.ca/cri/'
describe("UNB LIB Scopus Publications Crawler", {baseUrl: host, groups: ['sites']}, ()=>{

  context('CRI Page', {baseUrl: host}, () => {
    beforeEach(() => {
      cy.visit('/')
      cy.title()
        .should('contain', 'CRI-affiliated publications')
    })

    specify('Past publications list should return 900+ results', () => {
      cy.get('[data-test-id="pastBtn"]')
        .click()
      cy.url()
        .should('match', /https:\/\/pubcrawler.lib.unb.ca\/cri\//)
      cy.get('[data-test-id="pubItem"]')
        .should('have.lengthOf.at.least', 900)
    });
  })
})
